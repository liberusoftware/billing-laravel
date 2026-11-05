<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Enums\BillingCycle;
use App\Models\Products_Service;
use App\Models\Subscription;
use App\Models\SubscriptionChange;
use App\Services\ServiceProvisioningService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Throwable;

class ProcessSubscriptionChanges extends Command
{
    protected $signature = 'subscriptions:process-changes';

    protected $description = 'Apply paid upgrades and due subscription changes';

    public function handle(ServiceProvisioningService $services): int
    {
        foreach (SubscriptionChange::query()->with(['subscription.productService', 'invoice', 'targetProduct'])->whereIn('status', ['pending', 'queued'])->lazyById() as $change) {
            $subscription = $change->subscription;
            $invoice = $change->invoice;
            $target = $change->targetProduct;
            if ($invoice?->status !== 'paid' || $subscription === null || $target === null
                || $invoice->subscription_id !== $subscription->id
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
                $subscription->update(['product_service_id' => $target->getKey(), 'price' => round((float) $target->price * (BillingCycle::tryFrom($subscription->renewal_period)?->priceMultiplier() ?? 1), 2)]);
                $change->update(['status' => 'completed', 'completed_at' => now(), 'last_error' => null]);
            } catch (Throwable $e) {
                $change->update(['status' => 'failed', 'last_error' => $e->getMessage()]);
                $this->error($e->getMessage());
            }
        }

        foreach (Subscription::query()->whereNotNull('scheduled_change')->where('end_date', '<=', now())->lazyById() as $candidate) {
            try {
                DB::transaction(function () use ($candidate, $services): void {
                    $subscription = Subscription::query()->lockForUpdate()->findOrFail($candidate->id);
                    $scheduled = $subscription->scheduled_change;
                    if ($scheduled === null || $subscription->status !== 'active' || $subscription->end_date?->isFuture()) {
                        return;
                    }

                    if (($scheduled['type'] ?? null) === 'cancel') {
                        if (! $services->manageService($subscription, 'terminate')) {
                            throw new \RuntimeException('The provider rejected the scheduled cancellation.');
                        }
                        $subscription->update(['status' => 'cancelled', 'auto_renew' => false, 'scheduled_change' => null]);
                    } elseif (($scheduled['type'] ?? null) === 'downgrade') {
                        $target = Products_Service::query()->findOrFail($scheduled['product_service_id']);
                        $current = $subscription->productService;
                        if ($current === null || $target->type !== $current->type || $target->team_id !== $current->team_id
                            || $target->hosting_server_id !== $current->hosting_server_id || (float) $target->price >= (float) $current->price) {
                            throw new \RuntimeException('The scheduled downgrade is no longer eligible.');
                        }
                        if (! $services->manageService($subscription, 'downgrade', ['new_product' => $target])) {
                            throw new \RuntimeException('The provider rejected the scheduled downgrade.');
                        }
                        $subscription->update([
                            'product_service_id' => $target->id,
                            'price' => round((float) $target->price * (BillingCycle::tryFrom($subscription->renewal_period)?->priceMultiplier() ?? 1), 2),
                            'scheduled_change' => null,
                        ]);
                    }
                });
            } catch (Throwable $e) {
                $this->error($e->getMessage());
            }
        }

        return self::SUCCESS;
    }
}
