<?php

declare(strict_types=1);

namespace Liberu\Billing\Reporting\Queries;

use Illuminate\Database\Eloquent\Collection;
use Liberu\Billing\Reporting\Models\MetricSnapshot;

final class ListReportingRecords
{
    public function handle(int $teamId): Collection
    {
        return MetricSnapshot::query()->forTeam($teamId)->latest()->get();
    }
}
