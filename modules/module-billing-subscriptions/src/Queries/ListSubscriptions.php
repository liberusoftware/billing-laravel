<?php

declare(strict_types=1);

namespace Liberu\Billing\Subscriptions\Queries;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Liberu\Billing\Subscriptions\Models\Subscription;

final class ListSubscriptions
{
    public function execute(?int $teamId, int $perPage = 25): LengthAwarePaginator
    {
        return Subscription::query()
            ->when($teamId !== null, fn ($query) => $query->where('team_id', $teamId))
            ->latest()
            ->paginate(min(max($perPage, 1), 100));
    }
}
