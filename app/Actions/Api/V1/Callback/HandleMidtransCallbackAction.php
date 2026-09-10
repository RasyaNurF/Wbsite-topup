<?php

namespace App\Actions\Api\V1\Callback;

use App\Enums\PaymentStatusEnum;
use App\Jobs\SendMidtransPaymentNotification;
use App\Mail\PaymentFailed;
use App\Mail\PaymentSuccess;
use App\Models\Order\Order;
use App\Models\Payment\Payment;
use App\Services\MidtransService;
use App\Services\VodaService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Triyatna\Digiflazz\Digiflazz;

class HandleMidtransCallbackAction
{
    public function __construct(
        public readonly MidtransService $midtransService,
        public readonly VodaService $vodaService,
    ) {}

    /**
     * @param  array{order_id: string, transaction_id: string, status_code: string, gross_amount: string, signature_key: string, transaction_status: string, fraud_status?: string|null}  $payload
     */
    public function handle(array $payload): Payment
    {
        if (! $this->midtransService->validateSignature(
            $payload['order_id'],
            $payload['status_code'],
            $payload['gross_amount'],
            $payload['signature_key'],
        )) {
            abort(403, 'Invalid signature key');
        }

        $payment = Payment::query()
            ->where('driver', 'midtrans')
            ->where('order_id', $payload['order_id'])
            ->firstOrFail();

        return $this->applyVerifiedStatus($payment, $payload);
    }

    /**
     * Apply a signed notification or an authenticated Get Status API response.
     *
     * @param  array<string, mixed>  $payload
     */
    public function applyVerifiedStatus(Payment $payment, array $payload): Payment
    {
        return DB::transaction(function () use ($payment, $payload): Payment {
            $payment = Payment::query()->lockForUpdate()->findOrFail($payment->id);

            abort_unless(
                $payment->driver === 'midtrans'
                && ($payload['order_id'] ?? null) === $payment->order_id
                && is_numeric($payload['gross_amount'] ?? null)
                && (float) $payload['gross_amount'] === (float) $payment->amount
                && filled($payload['transaction_id'] ?? null)
                && (! $payment->transaction_id || $payment->transaction_id === $payload['transaction_id']),
                422,
                'Payment details do not match',
            );

            if ($payment->paid_at) {
                return $payment;
            }

            $status = match ($payload['transaction_status'] ?? null) {
                'settlement' => PaymentStatusEnum::SETTLEMENT,
                'capture' => ($payload['fraud_status'] ?? null) === 'accept'
                    ? PaymentStatusEnum::SETTLEMENT : null,
                'deny' => PaymentStatusEnum::DENY,
                'expire' => PaymentStatusEnum::EXPIRED,
                'cancel' => PaymentStatusEnum::CANCEL,
                default => null,
            };

            $order = $payment->payable;

            if (! $status || ! $order instanceof Order || $order->payment_status === $status) {
                return $payment;
            }

            $isSuccess = $status === PaymentStatusEnum::SETTLEMENT;

            $payment->update([
                'transaction_id' => $payload['transaction_id'],
                ...($isSuccess ? ['paid_at' => now()] : ['expired_at' => now()]),
            ]);
            $order->update(['payment_status' => $status]);

            SendMidtransPaymentNotification::dispatch($order, $isSuccess)->afterCommit();

            return $payment;
        });
    }

    public function sendOrderNotification(Order $order, bool $isSuccess): void
    {
        if ($isSuccess) {
            // Proccess Transaction
            $accountId = $order->submited['account_id'] ?? '';
            $serverId = $order->submited['server_id'] ?? '';
            $customer = $accountId.$serverId;

            // If the provider is Digiflazz, create transaction to Digiflazz
            if ($order->brand?->provider === 'digiflazz' && $order->product) {
                Digiflazz::createPrepaidTransaction(
                    productCode: $order->product->sku,
                    customerNo: $customer,
                    refId: $order->reference,
                );
            }

            // Send notification to user
            $message = getSetting('template_payment_confirmation');
            $message = str_replace('{customer_name}', $order->name, $message);
            $message = str_replace('{order_id}', $order->reference, $message);
            $message = str_replace('{app_name}', config('app.name'), $message);
            $message = str_replace('{link}', route('transaction.show', [
                'order' => $order,
            ]), $message);
            $message = str_replace('{cs_link}', getSetting('cs'), $message);
        } else {
            // Send notification to user
            $message = getSetting('template_payment_rejected');
            $message = str_replace('{customer_name}', $order->name, $message);
            $message = str_replace('{order_id}', $order->reference, $message);
            $message = str_replace('{app_name}', config('app.name'), $message);
            $message = str_replace('{link}', route('transaction.show', [
                'order' => $order,
            ]), $message);
            $message = str_replace('{cs_link}', getSetting('cs'), $message);
        }

        // // Send message via Voda
        // $isNotificationError = false;
        // try {
        //     // Send message via Voda
        //     $this->vodaService->sendMessage(
        //         phone: $order->phone,
        //         message: $message,
        //         linkPreview: true,
        //     );
        // } catch (\Exception $e) {
        //     Log::error('Failed to send Voda message: '.$e->getMessage());
        //     $isNotificationError = true;
        // }

        // // Create notification record
        // $order->notifications()->create([
        //     'provider' => 'voda',
        //     'title' => 'Payment '.($isSuccess ? 'Confirmed' : 'Rejected'),
        //     'content' => $message,
        //     'error' => $isNotificationError,
        // ]);

        // Send message via email
        Mail::to($order->email)->send(
            $isSuccess ? new PaymentSuccess($order) : new PaymentFailed($order)
        );

        $order->notifications()->create([
            'provider' => 'email',
            'title' => 'Payment '.($isSuccess ? 'Confirmed' : 'Rejected'),
            'content' => $message,
            'error' => false,
        ]);
    }
}
