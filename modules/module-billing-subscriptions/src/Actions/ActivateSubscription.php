<?php

declare(strict_types=1);

namespace Liberu\Billing\Subscriptions\Actions;

use Illuminate\Database\DatabaseManager;
use Liberu\Billing\Subscriptions\Enums\SubscriptionStatus;
use Liberu\Billing\Subscriptions\Events\SubscriptionActivated;
use Liberu\Billing\Subscriptions\Models\Subscription;

final readonly class ActivateSubscription
{
    public function __construct(private DatabaseManager $database) {}

    /** @param array<string,mixed> $attributes */
    public function execute(array $attributes): Subscription
    {
        $startsAt = $attributes['starts_at'] ?? now();
        $trialDays = max(0, (int) ($attributes['trial_days'] ?? 0));
        $trialEndsAt = $trialDays > 0 ? now()->parse($startsAt)->addDays($trialDays) : null;

        if (($attributes['team_id'] ?? null) === null && ($attributes['customer_id'] ?? null) === null) {
            throw new \InvalidArgumentException('A team or customer is required.');
        }

        $subscription = $this->database->transaction(function () use ($attributes, $startsAt, $trialEndsAt, $trialDays): Subscription {
            return Subscription::query()->create([
                'team_id' => $attributes['team_id'] ?? null,
                'customer_id' => $attributes['customer_id'] ?? null,
                'pricing_plan_id' => $attributes['pricing_plan_id'] ?? null,
                'status' => $trialDays > 0 ? SubscriptionStatus::Trialing : SubscriptionStatus::Active,
                'starts_at' => $startsAt,
                'trial_ends_at' => $trialEndsAt,
                'current_period_ends_at' => $attributes['current_period_ends_at'] ?? null,
                'auto_renew' => $attributes['auto_renew'] ?? true,
                'entitlement_state' => $attributes['entitlement_state'] ?? ['active' => true],
                'metadata' => $attributes['metadata'] ?? [],
            ]);
        });

        SubscriptionActivated::dispatch($subscription);

        return $subscription;
    }
}
