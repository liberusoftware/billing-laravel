<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\DomainRenewal;
use App\Services\DomainService;
use Illuminate\Console\Command;
use Throwable;

class ProcessDomainRenewals extends Command
{
    protected $signature = 'domains:process-renewals';

    protected $description = 'Process paid domain renewal requests';

    public function handle(DomainService $domains): int
    {
        foreach (DomainRenewal::query()->where('status', 'pending')->lazyById() as $renewal) {
            $subscription = $renewal->subscription;
            if ($renewal->invoice?->status !== 'paid' || $subscription === null
                || $subscription->customer_id !== $renewal->invoice->customer_id
                || $subscription->domain_name !== $renewal->domain
                || $subscription->domain_registrar !== $renewal->registrar) {
                continue;
            }
            if (DomainRenewal::query()->whereKey($renewal->getKey())->where('status', 'pending')->update(['status' => 'processing']) !== 1) {
                continue;
            }
            $renewal->increment('attempts');
            try {
                $domains->renewDomain($subscription);
                $renewal->update(['status' => 'completed', 'completed_at' => now(), 'last_error' => null]);
            } catch (Throwable $e) {
                // An ambiguous provider response needs review before another paid renewal.
                $renewal->update(['status' => 'failed', 'last_error' => $e->getMessage()]);
                $this->error($e->getMessage());
            }
        }

        return self::SUCCESS;
    }
}
