<?php

declare(strict_types=1);

namespace Liberu\Billing\Reporting\Actions;

use Illuminate\Support\Facades\DB;
use Liberu\Billing\Reporting\Models\ReportingMetric;

final class RecordReportingMetric
{
    /** @param array<string,mixed> $attributes */
    public function execute(int $teamId, array $attributes): ReportingMetric
    {
        $metric = (string) ($attributes['metric'] ?? '');
        if ($teamId < 1 || ! in_array($metric, ['mrr', 'arr', 'churn', 'aging', 'revenue', 'tax', 'usage', 'provisioning', 'collection', 'provider'], true) || ! isset($attributes['period_start'], $attributes['period_end'])) {
            throw new \InvalidArgumentException('Reporting metric details are invalid.');
        }

return DB::transaction(fn (): ReportingMetric => ReportingMetric::query()->updateOrCreate(['team_id' => $teamId, 'metric' => $metric, 'period_start' => $attributes['period_start'], 'period_end' => $attributes['period_end'], 'source' => $attributes['source'] ?? null], ['value' => $attributes['value'] ?? 0, 'currency' => $attributes['currency'] ?? null, 'dimensions' => $attributes['dimensions'] ?? []]));
    }
}
