<?php

declare(strict_types=1);

namespace Liberu\Billing\Usage\Queries;

use Illuminate\Support\Collection;
use Liberu\Billing\Usage\Models\UsageRecord;

final class ListUsageRecords
{
    /** @return Collection<int, UsageRecord> */
    public function execute(?int $teamId = null, ?int $meterId = null): Collection
    {
        return UsageRecord::query()
            ->where(fn ($query) => $teamId === null
                ? $query->whereNull('team_id')
                : $query->whereNull('team_id')->orWhere('team_id', $teamId))
            ->when($meterId !== null, fn ($query) => $query->where('meter_id', $meterId))
            ->latest('occurred_at')
            ->get();
    }
}
