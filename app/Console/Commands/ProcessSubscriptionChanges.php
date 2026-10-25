<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\SubscriptionChange;
use App\Services\ServiceProvisioningService;
use Illuminate\Console\Command;
use Throwable;

class ProcessSubscriptionChanges extends Command
{
    protected $signature = 'subscriptions:process-changes';

    protected $description = 'Apply paid subscription upgrades';

    public function handle(ServiceProvisioningService $services): int
    {
        foreach (SubscriptionChange::query()->whereIn('status', ['pending', 'queued'])->lazyById() as $change) {
            $subscription = $change->subscription;
            $invoice = $change->invoice;
            $target = $change->targetProduct;
            if ($invoice?->status !== 'paid' || $subscription === null || $target === null
                || $subscription->customer_id !== $invoice->customer_id
                || $change->customer_id !== $invoice->customer_id) {
                continue;
            }
            if (SubscriptionChange::query()->whereKey($change->getKey())->whereIn('status', ['pending', 'queued'])->update(['status' => 'processing']) !== 1) {
                continue;
            }
            try {
                if ($target->type !== $subscription->productService?->type || $change->type !== 'upgrade') {
                    throw new \RuntimeException('Unsupported subscription change.');
                }
                $result = $services->manageService($subscription, 'upgrade', ['new_product' => $target]);
                if (! $result) {
                    throw new \RuntimeException('The provider rejected the subscription change.');
                }
                $subscription->update(['product_service_id' => $target->getKey()]);
                $change->update(['status' => 'completed', 'completed_at' => now(), 'last_error' => null]);
            } catch (Throwable $e) {
                $change->update(['status' => 'failed', 'last_error' => $e->getMessage()]);
                $this->error($e->getMessage());
            }
        }

        return self::SUCCESS;
    }
}
