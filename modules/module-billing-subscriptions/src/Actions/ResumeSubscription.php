<?php

declare(strict_types=1);

namespace Liberu\Billing\Subscriptions\Actions;

use Illuminate\Database\DatabaseManager;
use Liberu\Billing\Subscriptions\Enums\SubscriptionStatus;
use Liberu\Billing\Subscriptions\Events\SubscriptionResumed;
use Liberu\Billing\Subscriptions\Models\Subscription;

final readonly class ResumeSubscription
{
    public function __construct(private DatabaseManager $database) {}

    public function execute(Subscription $subscription): Subscription
    {
        if ($subscription->status !== SubscriptionStatus::Paused) {
            throw new \LogicException('Only paused subscriptions can be resumed.');
        }

        $resumed = $this->database->transaction(function () use ($subscription): Subscription {
            $locked = Subscription::query()->lockForUpdate()->findOrFail($subscription->getKey());
            if ($locked->status !== SubscriptionStatus::Paused) {
                throw new \LogicException('Only paused subscriptions can be resumed.');
            }

            $locked->update([
                'status' => SubscriptionStatus::Active,
                'paused_at' => null,
                'entitlement_state' => array_merge($locked->entitlement_state ?? [], ['active' => true]),
            ]);

            return $locked->refresh();
        });

        SubscriptionResumed::dispatch($resumed);

        return $resumed;
    }
}
