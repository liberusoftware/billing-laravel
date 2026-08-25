<?php

declare(strict_types=1);

namespace Liberu\Billing\Catalog\Queries;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;

final class ListCatalogRecords
{
    public function execute(string $model, ?int $teamId, int $perPage = 25): LengthAwarePaginator
    {
        return $model::query()->where(function ($query) use ($teamId): void {
            $query->whereNull('team_id')->when($teamId !== null, fn ($q) => $q->orWhere('team_id', $teamId));
        })->latest('id')->paginate(min(max($perPage, 1), 100));
    }
}
