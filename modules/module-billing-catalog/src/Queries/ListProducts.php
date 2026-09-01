<?php

declare(strict_types=1);

namespace Liberu\Billing\Catalog\Queries;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Liberu\Billing\Catalog\Models\Product;

final class ListProducts
{
    public function execute(?int $teamId, int $perPage = 25): LengthAwarePaginator
    {
        return Product::query()
            ->where(fn ($query) => $teamId === null
                ? $query->whereNull('team_id')
                : $query->whereNull('team_id')->orWhere('team_id', $teamId))
            ->where('status', '!=', 'archived')
            ->latest('id')
            ->paginate(min(max($perPage, 1), 100));
    }
}
