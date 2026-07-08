<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Events\InvoiceStatusChanged;
use App\Services\DomainService;
use Illuminate\Support\Facades\Cache;

class RenewDomainOnPayment
{
    public function __construct(protected DomainService $domainService) {}

    public function handle(InvoiceStatusChanged $event): void
    {
        if ($event->status !== 'paid') {
            return;
        }

        $subscription = $event->invoice->subscription;

        if (! $subscription || ! $subscription->domain_name || ! $subscription->domain_registrar) {
            return;
        }

        // ponytail: per-invoice cache guard for idempotency. Cache::add is atomic and
        // returns false if the key already exists. A persisted invoice column would
        // survive a cache flush — add one if a double-renew ever costs real money.
        if (! Cache::add("domain-renewal:invoice:{$event->invoice->id}", true, now()->addYear())) {
            return;
        }

        $this->domainService->renewDomain($subscription);
    }
}
