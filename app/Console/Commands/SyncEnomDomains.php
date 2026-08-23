<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\DomainPricingService;
use Exception;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Description('Synchronize domain pricing and TLDs from Enom')]
#[Signature('enom:sync-domains')]
class SyncEnomDomains extends Command
{
    public function __construct(protected DomainPricingService $domainPricingService)
    {
        parent::__construct();
    }

    public function handle(): void
    {
        $this->info('Starting Enom domain synchronization...');

        try {
            $this->domainPricingService->syncTldsFromEnom();
            $this->info('Enom domain synchronization completed successfully.');
        } catch (Exception $e) {
            $this->error('An error occurred during Enom domain synchronization: '.$e->getMessage());
        }
    }
}
