<?php

declare(strict_types=1);

namespace Liberu\Billing\Collections\Queries;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Liberu\Billing\Collections\Models\CollectionCase;

final class ListCollectionCases
{
    public function execute(?int $teamId, int $perPage = 25): LengthAwarePaginator
    {
        return CollectionCase::query()
            ->where(fn ($query) => $teamId === null
                ? $query->whereNull('team_id')
                : $query->where('team_id', $teamId)->orWhereNull('team_id'))
            ->latest()
            ->paginate(min(max($perPage, 1), 100));
    }
}
