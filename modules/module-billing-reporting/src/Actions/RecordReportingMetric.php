<?php

declare(strict_types=1);

namespace Liberu\Billing\Reporting\Actions;

use Illuminate\Support\Facades\DB;
use Carbon\CarbonImmutable;
use Liberu\Billing\Reporting\Enums\ReportingMetricType;
use Liberu\Billing\Reporting\Models\ReportingMetric;

final class RecordReportingMetric
{
    /** @param array<string,mixed> $attributes */
    public function execute(int $teamId, array $attributes): ReportingMetric
    {
        $metric = (string) ($attributes['metric'] ?? '');
        if ($teamId < 1 || ReportingMetricType::tryFrom($metric) === null || ! isset($attributes['period_start'], $attributes['period_end']) || ! is_numeric($attributes['value'] ?? null)) {
            throw new \InvalidArgumentException('Reporting metric details are invalid.');
        }

        try {
            $periodStart = CarbonImmutable::parse($attributes['period_start'])->startOfDay();
            $periodEnd = CarbonImmutable::parse($attributes['period_end'])->startOfDay();
        } catch (\Throwable $exception) {
            throw new \InvalidArgumentException('Reporting metric dates are invalid.', previous: $exception);
        }
        if ($periodEnd->lt($periodStart)) {
            throw new \InvalidArgumentException('The reporting period must end on or after it starts.');
        }
        $currency = $attributes['currency'] ?? null;
        if ($currency !== null && ! preg_match('/^[A-Za-z]{3}$/', (string) $currency)) {
            throw new \InvalidArgumentException('Reporting metric currency is invalid.');
        }

        return DB::transaction(fn (): ReportingMetric => ReportingMetric::query()->updateOrCreate(
            ['team_id' => $teamId, 'metric' => $metric, 'period_start' => $periodStart, 'period_end' => $periodEnd, 'source' => $attributes['source'] ?? null],
            ['value' => $attributes['value'], 'currency' => $currency === null ? null : strtoupper((string) $currency), 'dimensions' => $attributes['dimensions'] ?? []],
        ));
    }
}
