<?php

declare(strict_types=1);

namespace Liberu\Billing\Communications\Queries;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

final class ListCommunicationRecords
{
    /** @return Collection<int, Model> */
    public function handle(string $model, int $teamId): Collection
    {
        return $model::query()->where('team_id', $teamId)->latest()->get();
    }
}
