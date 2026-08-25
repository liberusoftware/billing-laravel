<?php

declare(strict_types=1);

namespace Liberu\Billing\Collections\Queries;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Liberu\Billing\Collections\Models\CollectionCase;

final class ListCollectionCases
{
    public function execute(?int $teamId, int $perPage = 25): LengthAwarePaginator
    {
        return CollectionCase::query()->when($teamId !== null, fn ($query) => $query->where('team_id', $teamId))->latest()->paginate(min(max($perPage, 1), 100));
    }
}
