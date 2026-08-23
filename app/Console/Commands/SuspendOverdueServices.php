<?php

namespace App\Console\Commands;

use App\Services\ServiceAutomationService;
use Exception;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Description('Suspend services with overdue invoices')]
#[Signature('services:suspend-overdue {--days=7 : Number of days overdue before suspension}')]
class SuspendOverdueServices extends Command
{
    public function __construct(
        protected ?ServiceAutomationService $automationService = null
    ) {
        parent::__construct();
        $this->automationService = $automationService ?? app(ServiceAutomationService::class);
    }

    public function handle(): int
    {
        if (cache()->get('suspending_overdue_services')) {
            $this->warn('Service suspension is already running');

            return Command::FAILURE;
        }

        cache()->put(
            'suspending_overdue_services',
            true,
            60
        ); // Lock for 60 minutes

        try {
            $days = (int) $this->option('days');
            $this->info("Suspending services with invoices overdue by {$days} days...");

            $suspended = $this->automationService->suspendOverdueServices($days);

            $this->info("Suspended {$suspended} services");

            cache()->forget('suspending_overdue_services');

            return Command::SUCCESS;
        } catch (Exception $e) {
            cache()->forget('suspending_overdue_services');
            $this->error('Error suspending services: '.$e->getMessage());

            return Command::FAILURE;
        }
    }
}
