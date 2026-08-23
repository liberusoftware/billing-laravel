<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\BillingService;
use Exception;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Description('Send SMS reminders for upcoming invoice due dates')]
#[Signature('invoices:send-reminders')]
class SendInvoiceReminders extends Command
{
    public function handle(BillingService $billingService): int
    {
        $this->info('Sending invoice reminders...');

        try {
            $billingService->sendUpcomingDueReminders();
            $this->info('Upcoming due date reminders sent successfully.');

            $billingService->sendOverdueReminders();
            $this->info('Overdue reminders sent successfully.');

        } catch (Exception $e) {
            $this->error('Error sending reminders: '.$e->getMessage());

            return 1;
        }

        return 0;
    }
}
