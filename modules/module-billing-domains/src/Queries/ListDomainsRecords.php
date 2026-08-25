<?php

declare(strict_types=1);

namespace Liberu\Billing\Domains\Queries;

use Illuminate\Database\Eloquent\Collection;
use Liberu\Billing\Domains\Models\Domain;

final class ListDomainsRecords
{
    public function handle(int $teamId): Collection
    {
        return Domain::query()->forTeam($teamId)->latest()->get();
    }
}
