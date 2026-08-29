<?php

declare(strict_types=1);

namespace Liberu\Billing\Subscriptions\Queries;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Liberu\Billing\Subscriptions\Enums\SubscriptionStatus;
use Liberu\Billing\Subscriptions\Models\Subscription;

final class ListSubscriptions
{
    public function execute(?int $teamId, int $perPage = 25, ?int $customerId = null, ?SubscriptionStatus $status = null): LengthAwarePaginator
    {
        return Subscription::query()
            ->where('team_id', $teamId ?? -1)
            ->when($customerId !== null, fn ($query) => $query->where('customer_id', $customerId))
            ->when($status !== null, fn ($query) => $query->where('status', $status->value))
            ->latest()
            ->paginate(min(max($perPage, 1), 100));
    }
}
