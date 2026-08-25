<?php

declare(strict_types=1);

namespace Liberu\Billing\Usage\Queries;

use Illuminate\Support\Collection;
use Liberu\Billing\Usage\Models\Meter;

final class ListMeters
{
    /** @return Collection<int, Meter> */
    public function execute(?int $teamId = null): Collection
    {
        return Meter::query()
            ->where(fn ($query) => $teamId === null
                ? $query->whereNull('team_id')
                : $query->whereNull('team_id')->orWhere('team_id', $teamId))
            ->latest('id')
            ->get();
    }
}
