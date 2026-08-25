<?php

declare(strict_types=1);

namespace Liberu\Billing\Reporting\Queries;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Liberu\Billing\Reporting\Models\MetricSnapshot;

final class ListMetricSnapshots
{
    public function execute(int $teamId, int $perPage = 25): LengthAwarePaginator
    {
        return MetricSnapshot::query()->forTeam($teamId)->latest('id')->paginate(min(max($perPage, 1), 100));
    }
}
