<?php

declare(strict_types=1);

namespace Liberu\Billing\Subscriptions\Actions;

use Illuminate\Database\DatabaseManager;
use Liberu\Billing\Subscriptions\Enums\SubscriptionStatus;
use Liberu\Billing\Subscriptions\Events\SubscriptionRenewed;
use Liberu\Billing\Subscriptions\Models\Subscription;

final readonly class RenewSubscription
{
    public function __construct(private DatabaseManager $database) {}

    public function execute(Subscription $subscription, int $periodDays = 30): Subscription
    {
        if (! $subscription->auto_renew || in_array($subscription->status, [SubscriptionStatus::Cancelled, SubscriptionStatus::Expired], true)) {
            throw new \LogicException('Subscription cannot be renewed.');
        }
        if ($periodDays < 1) {
            throw new \InvalidArgumentException('Renewal period must be positive.');
        }

        $renewed = $this->database->transaction(function () use ($subscription, $periodDays): Subscription {
            $base = $subscription->current_period_ends_at?->copy() ?? now();
            $subscription->update([
                'status' => SubscriptionStatus::Active,
                'current_period_ends_at' => $base->addDays($periodDays),
                'cancelled_at' => null,
                'paused_at' => null,
                'entitlement_state' => array_merge($subscription->entitlement_state ?? [], ['active' => true]),
            ]);

            return $subscription->refresh();
        });
        SubscriptionRenewed::dispatch($renewed);

        return $renewed;
    }
}
