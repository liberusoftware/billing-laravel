<?php

declare(strict_types=1);

namespace Liberu\Billing\Reporting\Queries;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Liberu\Billing\Reporting\Models\ReportingMetric;

final class ListReportingMetrics
{
    public function execute(int $teamId, ?string $metric = null, int $perPage = 25): LengthAwarePaginator
    {
        return ReportingMetric::query()
            ->where('team_id', $teamId)
            ->when($metric !== null, fn ($query) => $query->where('metric', $metric))
            ->latest('period_end')
            ->latest('id')
            ->paginate(min(max($perPage, 1), 100));
    }
}
