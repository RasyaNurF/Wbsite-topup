<?php

use App\Actions\Api\V1\Callback\HandleMidtransCallbackAction;
use App\Actions\Main\SyncMidtransPaymentAction;
use App\Enums\PaymentStatusEnum;
use App\Jobs\ExpirePayments;
use App\Jobs\SendMidtransPaymentNotification;
use App\Mail\PaymentSuccess;
use App\Models\Order\Order;
use App\Models\PPOB\PPOBBrand;
use App\Models\PPOB\PPOBCategory;
use App\Models\User;
use App\Services\MidtransService;
use Database\Seeders\SettingSeeder;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    config(['midtrans.server_key' => 'test-server-key', 'midtrans.is_production' => false, 'inertia.ssr.enabled' => false]);
    Http::preventStrayRequests();
    Bus::fake([SendMidtransPaymentNotification::class]);
    $category = PPOBCategory::create(['name' => 'Games']);
    $brand = PPOBBrand::create(['p_p_o_b_category_id' => $category->id, 'name' => 'Test Brand', 'provider' => 'manual_topup']);
    $this->order = Order::create([
        'user_id' => User::factory()->create()->id,
        'p_p_o_b_brand_id' => $brand->id,
        'reference' => 'TEST-ORDER-123',
        'ref_number' => 1234567890,
        'name' => 'Test Customer',
        'email' => 'customer@example.com',
        'phone' => '08123456789',
        'amount' => 10000,
        'fee' => 1000,
        'total_amount' => 11000,
        'payment_status' => PaymentStatusEnum::PENDING,
    ]);
    $this->payment = $this->order->payment()->create([
        'driver' => 'midtrans',
        'order_id' => 'PAYMENT-WITH-HYPHENS-123',
        'transaction_id' => 'midtrans-transaction-123',
        'amount' => 11000,
        'expired_at' => now()->addDay(),
    ]);
    $this->payload = [
        'order_id' => $this->payment->order_id,
        'transaction_id' => $this->payment->transaction_id,
        'status_code' => '200',
        'gross_amount' => '11000.00',
        'transaction_status' => 'settlement',
        'fraud_status' => 'accept',
        'signature_key' => hash('sha512', $this->payment->order_id.'20011000.00test-server-key'),
    ];
});

test('signed callbacks mark a payment paid only once and preserve its full order id', function () {
    $this->postJson(route('api.v1.midtrans.callback'), $this->payload)->assertOk();
    $paidAt = $this->payment->fresh()->paid_at;
    $this->travel(1)->minute();
    $this->postJson(route('api.v1.midtrans.callback'), $this->payload)->assertOk();
    $this->postJson(route('api.v1.midtrans.callback'), [...$this->payload, 'transaction_status' => 'pending'])->assertOk();
    expect($paidAt)->not->toBeNull()
        ->and($this->payment->fresh()->paid_at->equalTo($paidAt))->toBeTrue()
        ->and($this->order->fresh()->payment_status)->toBe(PaymentStatusEnum::SETTLEMENT);
    Bus::assertDispatchedTimes(SendMidtransPaymentNotification::class, 1);
});

test('invalid signatures cannot change a payment', function () {
    $this->postJson(route('api.v1.midtrans.callback'), [...$this->payload, 'signature_key' => str_repeat('a', 128)])->assertForbidden();
    expect($this->payment->fresh()->paid_at)->toBeNull();
    Bus::assertNothingDispatched();
});

test('malformed callbacks are rejected', function () {
    $this->postJson(route('api.v1.midtrans.callback'), [])->assertUnprocessable();
    expect($this->payment->fresh()->paid_at)->toBeNull();
});

test('mismatched payment details cannot mark an order paid', function (array $changes) {
    $payload = [...$this->payload, ...$changes];
    $payload['signature_key'] = hash('sha512', $payload['order_id'].$payload['status_code'].$payload['gross_amount'].'test-server-key');
    $this->postJson(route('api.v1.midtrans.callback'), $payload)->assertUnprocessable();
    expect($this->payment->fresh()->paid_at)->toBeNull();
    Bus::assertNothingDispatched();
})->with([
    'wrong amount' => [['gross_amount' => '1.00']],
    'wrong transaction' => [['transaction_id' => 'another-transaction']],
]);

test('callbacks cannot update a manual payment', function () {
    $this->payment->update(['driver' => 'manual']);
    $this->postJson(route('api.v1.midtrans.callback'), $this->payload)->assertNotFound();
    expect($this->payment->fresh()->paid_at)->toBeNull();
});

test('only accepted captures are paid', function (?string $fraudStatus, bool $paid) {
    $this->postJson(route('api.v1.midtrans.callback'), [...$this->payload, 'transaction_status' => 'capture', 'fraud_status' => $fraudStatus])->assertOk();
    expect($this->payment->fresh()->paid_at !== null)->toBe($paid);
})->with([
    'accepted' => ['accept', true],
    'challenged' => ['challenge', false],
    'denied' => ['deny', false],
    'missing' => [null, false],
]);

test('failed statuses remain unpaid and use the matching order status', function (string $status, PaymentStatusEnum $expected) {
    $payload = [...$this->payload, 'transaction_status' => $status];
    $this->postJson(route('api.v1.midtrans.callback'), $payload)->assertOk();
    $this->postJson(route('api.v1.midtrans.callback'), $payload)->assertOk();
    expect($this->payment->fresh()->paid_at)->toBeNull()
        ->and($this->payment->fresh()->expired_at->isPast())->toBeTrue()
        ->and($this->order->fresh()->payment_status)->toBe($expected);
    Bus::assertDispatchedTimes(SendMidtransPaymentNotification::class, 1);
})->with([
    'expired' => ['expire', PaymentStatusEnum::EXPIRED],
    'cancelled' => ['cancel', PaymentStatusEnum::CANCEL],
    'denied' => ['deny', PaymentStatusEnum::DENY],
]);

test('late settlement overrides local expiration', function () {
    $this->payment->update(['expired_at' => now()->subHour()]);
    $this->order->update(['payment_status' => PaymentStatusEnum::EXPIRED]);
    $this->postJson(route('api.v1.midtrans.callback'), $this->payload)->assertOk();
    expect($this->order->fresh()->payment_status)->toBe(PaymentStatusEnum::SETTLEMENT)
        ->and($this->payment->fresh()->paid_at)->not->toBeNull();
});

test('opening the transaction synchronizes its status without a webhook', function () {
    $this->seed(SettingSeeder::class);
    Http::fake(['*/v2/*/status' => Http::response($this->payload)]);
    $this->get(route('transaction.show', $this->order))->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('main/TransactionShow')
            ->where('order.payment_status', PaymentStatusEnum::SETTLEMENT->value)
            ->where('order.payment.paid_at', fn ($value) => filled($value)));
    Http::assertSent(fn (Request $request) => $request->url() === 'https://api.sandbox.midtrans.com/v2/midtrans-transaction-123/status'
        && $request->hasHeader('Authorization', 'Basic '.base64_encode('test-server-key:')));
});

test('polling is throttled and retries after a temporary API failure', function () {
    Http::fakeSequence()->push([], 503)->push($this->payload);
    $action = app(SyncMidtransPaymentAction::class);
    $action->handle($this->payment);
    $action->handle($this->payment);
    Http::assertSentCount(1);
    expect($this->payment->fresh()->paid_at)->toBeNull();
    $this->travel(11)->seconds();
    $action->handle($this->payment);
    Http::assertSentCount(2);
    expect($this->payment->fresh()->paid_at)->not->toBeNull();
});

test('pending API responses do not mark a payment paid', function () {
    Http::fake(['*/status' => Http::response([...$this->payload, 'transaction_status' => 'pending'])]);
    app(SyncMidtransPaymentAction::class)->handle($this->payment);
    expect($this->order->fresh()->payment_status)->toBe(PaymentStatusEnum::PENDING)
        ->and($this->payment->fresh()->paid_at)->toBeNull();
    Bus::assertNothingDispatched();
});

test('scheduled synchronization handles payments even when the page is closed', function () {
    Http::fake(['*/status' => Http::response($this->payload)]);
    $this->artisan('payments:sync-midtrans')->assertSuccessful();
    expect($this->order->fresh()->payment_status)->toBe(PaymentStatusEnum::SETTLEMENT);
    Bus::assertDispatchedTimes(SendMidtransPaymentNotification::class, 1);
});

test('manual and already paid payments never query Midtrans', function (string $state) {
    $this->payment->update($state === 'manual' ? ['driver' => 'manual'] : ['paid_at' => now()]);
    app(SyncMidtransPaymentAction::class)->handle($this->payment);
    Http::assertNothingSent();
})->with(['manual', 'paid']);

test('local expiration does not override an unconfirmed Midtrans status', function () {
    $this->payment->update(['expired_at' => now()->subHour()]);
    (new ExpirePayments)->handle();
    expect($this->order->fresh()->payment_status)->toBe(PaymentStatusEnum::PENDING);
});

test('charge requests use the server key as the basic authentication username', function () {
    Http::fake(['*/charge' => Http::response(['transaction_id' => 'test'])]);
    $service = app(MidtransService::class);
    $service->createQris('order-qris', 11000);
    $service->createBankTransfer('order-bank', 11000);
    $service->createCardTransaction('order-card', 11000, 'token');
    Http::assertSentCount(3);
    Http::assertNotSent(fn (Request $request) => ! $request->hasHeader('Authorization', 'Basic '.base64_encode('test-server-key:')));
});

test('payment notifications can run separately without sending duplicate confirmations', function () {
    $this->seed(SettingSeeder::class);
    Mail::fake();
    $this->order->update(['payment_status' => PaymentStatusEnum::SETTLEMENT]);
    $job = new SendMidtransPaymentNotification($this->order, true);
    $job->handle(app(HandleMidtransCallbackAction::class));
    $job->handle(app(HandleMidtransCallbackAction::class));

    Mail::assertQueued(PaymentSuccess::class, 1);
    expect($this->order->notifications()->count())->toBe(1);
});

test('a queued rejection is skipped if the payment has since settled', function () {
    Mail::fake();
    $this->order->update(['payment_status' => PaymentStatusEnum::SETTLEMENT]);
    (new SendMidtransPaymentNotification($this->order, false))->handle(app(HandleMidtransCallbackAction::class));
    Mail::assertNothingOutgoing();
});

test('scheduled synchronization recovers locally expired payments', function () {
    $this->order->update(['payment_status' => PaymentStatusEnum::EXPIRED]);
    Http::fake(['*/status' => Http::response($this->payload)]);
    $this->artisan('payments:sync-midtrans')->assertSuccessful();
    expect($this->order->fresh()->payment_status)->toBe(PaymentStatusEnum::SETTLEMENT);
});

test('a failed status request does not break the transaction page', function () {
    $this->seed(SettingSeeder::class);
    Http::fake(['*/status' => Http::response([], 503)]);
    $this->get(route('transaction.show', $this->order))->assertOk()
        ->assertInertia(fn (Assert $page) => $page->where('order.payment_status', PaymentStatusEnum::PENDING->value));
});
