<?php

declare(strict_types=1);

namespace Liberu\Billing\Subscriptions\Actions;

use Carbon\CarbonInterface;
use Illuminate\Database\DatabaseManager;
use Liberu\Billing\Subscriptions\Enums\SubscriptionStatus;
use Liberu\Billing\Subscriptions\Events\SubscriptionExpired;
use Liberu\Billing\Subscriptions\Models\Subscription;

final readonly class ExpireSubscriptions
{
    public function __construct(private DatabaseManager $database) {}

    /** @return int Number of subscriptions transitioned to expired. */
    public function execute(?int $teamId = null, ?CarbonInterface $asOf = null): int
    {
        $asOf ??= now();
        $query = Subscription::query()
            ->whereIn('status', [SubscriptionStatus::Active, SubscriptionStatus::Trialing])
            ->where('auto_renew', false)
            ->whereNotNull('current_period_ends_at')
            ->where('current_period_ends_at', '<=', $asOf)
            ->when($teamId !== null, fn ($query) => $query->where('team_id', $teamId));
        $expired = [];

        $this->database->transaction(function () use ($query, &$expired): void {
            $query->lockForUpdate()->each(function (Subscription $subscription) use (&$expired): void {
                $subscription->update([
                    'status' => SubscriptionStatus::Expired,
                    'entitlement_state' => array_merge($subscription->entitlement_state ?? [], ['active' => false]),
                ]);
                $expired[] = $subscription->refresh();
            });
        });

        foreach ($expired as $subscription) {
            SubscriptionExpired::dispatch($subscription);
        }

        return count($expired);
    }
}
