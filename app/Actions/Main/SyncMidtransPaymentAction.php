<?php

namespace App\Actions\Main;

use App\Actions\Api\V1\Callback\HandleMidtransCallbackAction;
use App\Models\Payment\Payment;
use App\Services\MidtransService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Throwable;

class SyncMidtransPaymentAction
{
    public function __construct(
        public readonly MidtransService $midtransService,
        public readonly HandleMidtransCallbackAction $callbackAction,
    ) {}

    public function handle(Payment $payment): Payment
    {
        if ($payment->driver !== 'midtrans' || $payment->paid_at || ! $payment->transaction_id
            || blank($this->midtransService->serverKey)) {
            return $payment;
        }

        if (! Cache::add('midtrans:status:'.$payment->id, true, now()->addSeconds(10))) {
            return $payment->refresh();
        }

        try {
            $payload = $this->midtransService->getTransactionStatus($payment->transaction_id);

            return $this->callbackAction->applyVerifiedStatus($payment, $payload);
        } catch (Throwable $exception) {
            Log::warning('Could not synchronize Midtrans payment', [
                'payment_id' => $payment->id,
                'error' => $exception->getMessage(),
            ]);

            return $payment->refresh();
        }
    }
}
