<?php

namespace App\Actions\Main;

use App\Mail\OrderCreated;
use App\Models\Cart\Cart;
use App\Models\Order\Order;
use App\Models\Payment\Payment;
use App\Models\Voucher\Voucher;
use App\Models\Voucher\VoucherUse;
use App\Services\MidtransService;
use App\Traits\WithGenerateReference;
use Illuminate\Support\Facades\Mail;

class StoreCartCheckoutAction
{
    use WithGenerateReference;

    public function __construct(
        public readonly MidtransService $midtransService,
    ) {}

    /**
     * Turn every item currently in the cart into a single order with one
     * line item per product, create the payment record, then empty the
     * cart. Mirrors StoreTransactionAction's payment/voucher/fee logic, but
     * for a basket of products instead of a single one.
     */
    public function handle(Cart $cart, array $data): Order
    {
        $cart->load('items.product');

        $items = $cart->items->filter(fn ($item) => $item->product !== null);

        if ($items->isEmpty()) {
            throw new \Exception('Keranjang kamu kosong.');
        }

        $amount = (int) $items->sum(fn ($item) => $item->price * $item->quantity);

        // Voucher logic (same rules as the single-product checkout).
        $voucher = null;
        $discountAmount = 0;
        if (! empty($data['voucher_code'])) {
            $voucher = Voucher::where('code', $data['voucher_code'])->first();

            if ($voucher) {
                $now = now();
                $isValid = $voucher->status
                    && (! $voucher->start_date || $now->gte($voucher->start_date))
                    && (! $voucher->end_date || $now->lte($voucher->end_date))
                    && ($voucher->usage_limit === 0 || $voucher->used_count < $voucher->usage_limit)
                    && ($voucher->min_purchase_amount === 0 || $amount >= $voucher->min_purchase_amount);

                if ($isValid) {
                    if ($voucher->type === 'FIXED_AMOUNT') {
                        $discountAmount = $voucher->fixed_amount;
                    } elseif ($voucher->type === 'PERCENTAGE') {
                        $discountAmount = ($amount * $voucher->percentage) / 100;
                    }
                    $discountAmount = min($discountAmount, $amount);
                }
            }
        }

        $amountAfterDiscount = $amount - $discountAmount;

        // Fee, same rules as the single-product checkout.
        if ($data['payment_type'] === 'automatic') {
            $fee = $data['payment_method'] === 'qris'
                ? $amountAfterDiscount * 0.007
                : 4000;
            $fee = (int) round($fee);
            $totalAmount = $amountAfterDiscount + $fee;
        } else {
            $fee = 0;
            $totalAmount = $amountAfterDiscount;
        }

        $reference = $this->generateReference(
            model: new Order,
            prefix: 'TRX-'.now()->format('Ymd').'-',
        );

        // The `orders` table requires a brand. A cart can span several
        // brands/products, so we record the first item's brand as a
        // representative value; the actual breakdown lives in `items`.
        $firstProduct = $items->first()->product;

        $order = Order::create([
            'user_id' => auth()->id(),
            'p_p_o_b_brand_id' => $firstProduct->p_p_o_b_brand_id,
            'p_p_o_b_product_id' => null,
            'reference' => $reference['code'],
            'ref_number' => $reference['number'],
            'name' => $data['name'],
            'email' => $data['email'],
            'phone' => $data['phone'],
            'submited' => [],
            'amount' => $amount,
            'fee' => $fee,
            'discount_amount' => $discountAmount,
            'total_amount' => $totalAmount,
        ]);

        foreach ($items as $item) {
            $brandId = $item->product->p_p_o_b_brand_id;

            $order->items()->create([
                'p_p_o_b_product_id' => $item->p_p_o_b_product_id,
                'quantity' => $item->quantity,
                'price' => $item->price,
                'submited' => [
                    'account_id' => $data['account_id'][$brandId] ?? null,
                    'server_id' => $data['server_id'][$brandId] ?? null,
                ],
            ]);
        }

        if ($voucher && $discountAmount > 0) {
            VoucherUse::create([
                'voucher_id' => $voucher->id,
                'usable_type' => Order::class,
                'usable_id' => $order->id,
                'before_amount' => $amount,
                'discount_amount' => $discountAmount,
                'after_amount' => $amountAfterDiscount,
            ]);

            $voucher->increment('used_count');
        }

        // Create payment record (same flow as the single-product checkout).
        $orderId = uniqid().time();
        $payment = Payment::create([
            'driver' => $data['payment_type'] === 'automatic' ? 'midtrans' : 'manual',
            'payable_type' => Order::class,
            'payable_id' => $order->id,
            'order_id' => $orderId,
            'transaction_id' => null,
            'payment_type' => $data['payment_method'] === 'qris' ? 'qris' : 'bank_transfer',
            'account_number' => 'AUTO_GENERATED',
            'channel' => $data['payment_method'],
            'expired_at' => now()->addHours(24),
            'amount' => $order->total_amount,
        ]);

        if ($data['payment_type'] === 'automatic') {
            if ($data['payment_method'] === 'qris') {
                $midtrans = $this->midtransService->createQris(
                    orderId: $payment->order_id,
                    amount: $payment->amount,
                );
            } else {
                $midtrans = $this->midtransService->createBankTransfer(
                    orderId: $payment->order_id,
                    bank: $payment->channel,
                    amount: $payment->amount,
                );
            }

            if (! $midtrans['successful']) {
                throw new \Exception('Failed to create Midtrans transaction: '.$midtrans['message']);
            }

            $payment->transaction_id = $midtrans['transaction_id'];
            $payment->account_number = $midtrans['account'];
            $payment->account_code = $midtrans['code'] ?? null;
            $payment->save();
        } else {
            $payment->channel = getSetting('manual_transfer_bank');
            $payment->account_number = getSetting('manual_transfer_account_number');
            $payment->account_code = getSetting('manual_transfer_account_name');
            $payment->save();
        }

        // Empty the cart now that the order has been created.
        $cart->items()->delete();

        Mail::to($order->email)->send(new OrderCreated($order));

        $order->notifications()->create([
            'provider' => 'email',
            'title' => 'Order Created',
            'content' => 'Cart checkout order created: '.$order->reference,
            'error' => false,
        ]);

        return $order;
    }
}
