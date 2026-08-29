<?php

declare(strict_types=1);

namespace Liberu\Billing\Subscriptions\Actions;

use Illuminate\Database\DatabaseManager;
use Illuminate\Support\Facades\Schema;
use Liberu\Billing\Subscriptions\Enums\SubscriptionStatus;
use Liberu\Billing\Subscriptions\Events\SubscriptionPlanChanged;
use Liberu\Billing\Subscriptions\Models\Subscription;

final readonly class ChangeSubscriptionPlan
{
    public function __construct(private DatabaseManager $database) {}

    public function execute(Subscription $subscription, ?int $pricingPlanId): Subscription
    {
        if (in_array($subscription->status, [SubscriptionStatus::Cancelled, SubscriptionStatus::Expired], true)) {
            throw new \LogicException('A terminal subscription cannot change plans.');
        }
        if ($pricingPlanId !== null && $pricingPlanId < 1) {
            throw new \InvalidArgumentException('Pricing plan is invalid.');
        }

        $changed = $this->database->transaction(function () use ($subscription, $pricingPlanId): Subscription {
            $locked = Subscription::query()->lockForUpdate()->findOrFail($subscription->getKey());
            if (in_array($locked->status, [SubscriptionStatus::Cancelled, SubscriptionStatus::Expired], true)) {
                throw new \LogicException('A terminal subscription cannot change plans.');
            }

            if ($pricingPlanId !== null && Schema::hasTable('billing_pricing_plans')) {
                $pricingPlan = $this->database->table('billing_pricing_plans')
                    ->where('id', $pricingPlanId)
                    ->first(['team_id']);

                if ($pricingPlan === null || ($pricingPlan->team_id !== null && ($locked->team_id === null || (int) $pricingPlan->team_id !== (int) $locked->team_id))) {
                    throw new \InvalidArgumentException('Subscription pricing plan reference is invalid.');
                }
            } elseif ($pricingPlanId !== null) {
                throw new \InvalidArgumentException('Subscription pricing plan reference is invalid.');
            }

            $locked->update(['pricing_plan_id' => $pricingPlanId, 'metadata' => array_merge($locked->metadata ?? [], ['plan_changed_at' => now()->toIso8601String()])]);

            return $locked->refresh();
        });

        SubscriptionPlanChanged::dispatch($changed);

        return $changed;
    }
}
