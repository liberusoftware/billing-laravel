<?php

declare(strict_types=1);

namespace Liberu\Billing\Core\Queries;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Model;

final class ListBillingRecords
{
    /** @param class-string<Model> $modelClass */
    public function execute(string $modelClass, ?int $teamId, int $perPage = 25): LengthAwarePaginator
    {
        return $modelClass::query()
            ->where(fn ($query) => $teamId === null
                ? $query->whereNull('team_id')
                : $query->whereNull('team_id')->orWhere('team_id', $teamId))
            ->latest('id')
            ->paginate(min(max($perPage, 1), 100));
    }
}
