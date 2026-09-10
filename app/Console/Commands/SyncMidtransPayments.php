<?php

namespace App\Console\Commands;

use App\Actions\Main\SyncMidtransPaymentAction;
use App\Enums\PaymentStatusEnum;
use App\Models\Order\Order;
use App\Models\Payment\Payment;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Builder;

class SyncMidtransPayments extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'payments:sync-midtrans';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Synchronize outstanding payments with Midtrans';

    /**
     * Execute the console command.
     */
    public function handle(SyncMidtransPaymentAction $action): int
    {
        Payment::query()
            ->where('driver', 'midtrans')
            ->whereNull('paid_at')
            ->whereNotNull('transaction_id')
            ->whereHasMorph('payable', Order::class, function (Builder $query): void {
                $query->where('payment_status', PaymentStatusEnum::PENDING)
                    ->orWhere(function (Builder $query): void {
                        $query->where('payment_status', PaymentStatusEnum::EXPIRED)
                            ->where('updated_at', '>=', now()->subDay());
                    });
            })
            ->lazyById(100)
            ->each(fn (Payment $payment) => $action->handle($payment));

        return self::SUCCESS;
    }
}
