<?php

namespace App\Jobs;

use App\Actions\Api\V1\Callback\HandleMidtransCallbackAction;
use App\Enums\PaymentStatusEnum;
use App\Models\Order\Order;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Throwable;

class SendMidtransPaymentNotification implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public function __construct(public Order $order, public bool $isSuccess) {}

    /** @return array<int, int> */
    public function backoff(): array
    {
        return [10, 60, 300];
    }

    /**
     * Execute the job.
     */
    public function handle(HandleMidtransCallbackAction $action): void
    {
        $this->order->refresh();

        if (! $this->isSuccess && $this->order->payment_status === PaymentStatusEnum::SETTLEMENT) {
            return;
        }

        if ($this->order->notifications()
            ->where('provider', 'email')
            ->where('title', $this->isSuccess ? 'Payment Confirmed' : 'Payment Rejected')
            ->exists()) {
            return;
        }

        $action->sendOrderNotification($this->order, $this->isSuccess);
    }

    public function failed(?Throwable $exception): void
    {
        Log::error('Midtrans payment notification failed', [
            'order_id' => $this->order->id,
            'error' => $exception?->getMessage(),
        ]);
    }
}
