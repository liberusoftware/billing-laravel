<?php

declare(strict_types=1);

namespace Liberu\Billing\Subscriptions\Actions;

use Illuminate\Database\DatabaseManager;
use Liberu\Billing\Subscriptions\Enums\SubscriptionStatus;
use Liberu\Billing\Subscriptions\Events\SubscriptionCancelled;
use Liberu\Billing\Subscriptions\Models\Subscription;

final readonly class CancelSubscription
{
    public function __construct(private DatabaseManager $database) {}

    public function execute(Subscription $subscription): Subscription
    {
        if ($subscription->status === SubscriptionStatus::Cancelled) {
            return $subscription;
        }

        $cancelled = $this->database->transaction(function () use ($subscription): Subscription {
            $subscription->update(['status' => SubscriptionStatus::Cancelled, 'cancelled_at' => now(), 'auto_renew' => false, 'entitlement_state' => array_merge($subscription->entitlement_state ?? [], ['active' => false])]);

            return $subscription->refresh();
        });
        SubscriptionCancelled::dispatch($cancelled);

        return $cancelled;
    }
}
