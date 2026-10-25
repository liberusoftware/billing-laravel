<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\HostingAccount;
use App\Models\HostingFulfillment;
use App\Services\ServiceProvisioningService;
use Illuminate\Console\Command;
use Throwable;

class DispatchHostingFulfillments extends Command
{
    protected $signature = 'hosting:dispatch-fulfillments';

    protected $description = 'Provision hosting for paid orders';

    public function handle(ServiceProvisioningService $services): int
    {
        foreach (HostingFulfillment::query()->where('status', 'pending')->lazyById() as $fulfillment) {
            $subscription = $fulfillment->subscription;
            if ($fulfillment->invoice?->status !== 'paid' || $subscription === null
                || $subscription->customer_id !== $fulfillment->invoice->customer_id) {
                continue;
            }
            if (HostingFulfillment::query()->whereKey($fulfillment->getKey())->where('status', 'pending')->update(['status' => 'processing']) !== 1) {
                continue;
            }
            $fulfillment->increment('attempts');
            try {
                $account = $subscription->hostingAccount ?? $services->provisionService($subscription);
                if (! $account instanceof HostingAccount || $account->status !== 'active') {
                    throw new \RuntimeException('Hosting provisioning requires review.');
                }
                $fulfillment->update(['status' => 'completed', 'completed_at' => now(), 'last_error' => null]);
                $fulfillment->order?->update(['status' => 'completed']);
            } catch (Throwable $e) {
                $fulfillment->update(['status' => 'failed', 'last_error' => $e->getMessage()]);
                $this->error($e->getMessage());
            }
        }

        return self::SUCCESS;
    }
}
