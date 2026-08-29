<?php

declare(strict_types=1);

namespace Liberu\Billing\Subscriptions\Actions;

use Illuminate\Database\DatabaseManager;
use Liberu\Billing\Subscriptions\Enums\SubscriptionStatus;
use Liberu\Billing\Subscriptions\Events\SubscriptionPaused;
use Liberu\Billing\Subscriptions\Models\Subscription;

final readonly class PauseSubscription
{
    public function __construct(private DatabaseManager $database) {}

    public function execute(Subscription $subscription): Subscription
    {
        if (in_array($subscription->status, [SubscriptionStatus::Cancelled, SubscriptionStatus::Expired], true)) {
            throw new \LogicException('A terminal subscription cannot be paused.');
        }

        $paused = $this->database->transaction(function () use ($subscription): Subscription {
            $locked = Subscription::query()->lockForUpdate()->findOrFail($subscription->getKey());
            if (in_array($locked->status, [SubscriptionStatus::Cancelled, SubscriptionStatus::Expired], true)) {
                throw new \LogicException('A terminal subscription cannot be paused.');
            }

            $locked->update(['status' => SubscriptionStatus::Paused, 'paused_at' => now(), 'entitlement_state' => array_merge($locked->entitlement_state ?? [], ['active' => false])]);

            return $locked->refresh();
        });

        SubscriptionPaused::dispatch($paused);

        return $paused;
    }
}
