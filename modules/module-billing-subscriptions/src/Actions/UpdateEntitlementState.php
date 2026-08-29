<?php

declare(strict_types=1);

namespace Liberu\Billing\Subscriptions\Actions;

use Illuminate\Database\DatabaseManager;
use Liberu\Billing\Subscriptions\Enums\SubscriptionStatus;
use Liberu\Billing\Subscriptions\Events\SubscriptionEntitlementsUpdated;
use Liberu\Billing\Subscriptions\Models\Subscription;

final readonly class UpdateEntitlementState
{
    public function __construct(private DatabaseManager $database) {}

    /** @param array<string,mixed> $entitlements */
    public function execute(Subscription $subscription, array $entitlements): Subscription
    {
        if (in_array($subscription->status, [SubscriptionStatus::Cancelled, SubscriptionStatus::Expired], true)) {
            throw new \LogicException('A terminal subscription cannot change entitlements.');
        }

        $updated = $this->database->transaction(function () use ($subscription, $entitlements): Subscription {
            $locked = Subscription::query()->lockForUpdate()->findOrFail($subscription->getKey());
            if (in_array($locked->status, [SubscriptionStatus::Cancelled, SubscriptionStatus::Expired], true)) {
                throw new \LogicException('A terminal subscription cannot change entitlements.');
            }

            $locked->update(['entitlement_state' => $entitlements]);

            return $locked->refresh();
        });

        SubscriptionEntitlementsUpdated::dispatch($updated);

        return $updated;
    }
}
