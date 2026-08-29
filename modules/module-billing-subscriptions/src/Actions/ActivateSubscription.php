<?php

declare(strict_types=1);

namespace Liberu\Billing\Subscriptions\Actions;

use Illuminate\Database\DatabaseManager;
use Illuminate\Support\Facades\Schema;
use Liberu\Billing\Subscriptions\Enums\SubscriptionStatus;
use Liberu\Billing\Subscriptions\Events\SubscriptionActivated;
use Liberu\Billing\Subscriptions\Models\Subscription;
use Liberu\Billing\Subscriptions\Support\CustomerReference;

final readonly class ActivateSubscription
{
    public function __construct(private DatabaseManager $database) {}

    /** @param array<string,mixed> $attributes */
    public function execute(array $attributes): Subscription
    {
        $startsAt = $attributes['starts_at'] ?? now();
        $trialDays = max(0, (int) ($attributes['trial_days'] ?? 0));
        $periodDays = (int) ($attributes['period_days'] ?? 30);
        if ($periodDays < 1) {
            throw new \InvalidArgumentException('Subscription period must be positive.');
        }
        $trialEndsAt = $trialDays > 0 ? now()->parse($startsAt)->addDays($trialDays) : null;

        if (($attributes['team_id'] ?? null) === null && ($attributes['customer_id'] ?? null) === null) {
            throw new \InvalidArgumentException('A team or customer is required.');
        }

        $pricingPlanId = $attributes['pricing_plan_id'] ?? null;
        $teamId = $attributes['team_id'] ?? null;
        $customerId = CustomerReference::assertBelongsToTeam($this->database, $attributes['customer_id'] ?? null, $teamId);

        if ($pricingPlanId !== null && Schema::hasTable('billing_pricing_plans')) {
            $pricingPlan = $this->database->table('billing_pricing_plans')
                ->where('id', (int) $pricingPlanId)
                ->first(['team_id']);

            if ($pricingPlan === null || ($pricingPlan->team_id !== null && ($teamId === null || (int) $pricingPlan->team_id !== (int) $teamId))) {
                throw new \InvalidArgumentException('Subscription pricing plan reference is invalid.');
            }
        } elseif ($pricingPlanId !== null) {
            throw new \InvalidArgumentException('Subscription pricing plan reference is invalid.');
        }

        $subscription = $this->database->transaction(function () use ($attributes, $startsAt, $trialEndsAt, $trialDays, $periodDays, $pricingPlanId, $customerId): Subscription {
            return Subscription::query()->create([
                'team_id' => $attributes['team_id'] ?? null,
                'customer_id' => $customerId,
                'pricing_plan_id' => $pricingPlanId,
                'status' => $trialDays > 0 ? SubscriptionStatus::Trialing : SubscriptionStatus::Active,
                'starts_at' => $startsAt,
                'trial_ends_at' => $trialEndsAt,
                'current_period_ends_at' => $attributes['current_period_ends_at'] ?? null,
                'period_days' => $periodDays,
                'auto_renew' => $attributes['auto_renew'] ?? true,
                'id_protection' => $attributes['id_protection'] ?? false,
                'entitlement_state' => $attributes['entitlement_state'] ?? ['active' => true],
                'metadata' => $attributes['metadata'] ?? [],
            ]);
        });

        SubscriptionActivated::dispatch($subscription);

        return $subscription;
    }
}
