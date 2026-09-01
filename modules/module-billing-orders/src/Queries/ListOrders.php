<?php

declare(strict_types=1);

namespace Liberu\Billing\Orders\Queries;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Liberu\Billing\Orders\Models\Order;

final class ListOrders
{
    public function execute(?int $teamId, int $perPage = 25): LengthAwarePaginator
    {
        return Order::query()
            ->where(fn ($query) => $teamId === null
                ? $query->whereNull('team_id')
                : $query->whereNull('team_id')->orWhere('team_id', $teamId))
            ->latest()
            ->paginate(min(max($perPage, 1), 100));
    }
}
