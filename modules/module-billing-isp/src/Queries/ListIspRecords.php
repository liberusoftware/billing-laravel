<?php

declare(strict_types=1);

namespace Liberu\Billing\Isp\Queries;

use Illuminate\Database\Eloquent\Collection;
use Liberu\Billing\Isp\Models\AccessService;

final class ListIspRecords
{
    public function handle(int $teamId): Collection
    {
        return AccessService::query()->forTeam($teamId)->latest()->get();
    }
}
