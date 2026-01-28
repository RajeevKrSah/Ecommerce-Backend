<?php

namespace App\Console\Commands;

use App\Services\PaymentService;
use Illuminate\Console\Command;

class CancelExpiredPayments extends Command
{
    protected $signature = 'payments:cancel-expired';
    protected $description = 'Cancel orders with expired payment intents';

    public function handle(PaymentService $paymentService): int
    {
        $this->info('Checking for expired payments...');

        $cancelledCount = $paymentService->cancelExpiredPayments();

        if ($cancelledCount > 0) {
            $this->info("Cancelled {$cancelledCount} expired payment(s)");
        } else {
            $this->info('No expired payments found');
        }

        return Command::SUCCESS;
    }
}
