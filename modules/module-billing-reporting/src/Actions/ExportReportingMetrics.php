<?php

declare(strict_types=1);

namespace Liberu\Billing\Reporting\Actions;

use Illuminate\Database\DatabaseManager;
use Illuminate\Support\Facades\Schema;

final readonly class ExportReportingMetrics
{
    public function __construct(private DatabaseManager $database) {}

    public function execute(int $teamId, ?string $metric = null): string
    {
        if ($teamId < 1) {
            throw new \InvalidArgumentException('A valid team is required.');
        }

        $rows = Schema::hasTable('billing_reporting_metrics')
            ? $this->database->table('billing_reporting_metrics')->where('team_id', $teamId)->when($metric !== null, fn ($query) => $query->where('metric', $metric))->orderBy('period_end')->orderBy('id')->get(['metric', 'period_start', 'period_end', 'value', 'currency', 'source'])
            : collect();

        $handle = fopen('php://temp', 'r+');
        fputcsv($handle, ['metric', 'period_start', 'period_end', 'value', 'currency', 'source'], escape: '\\');
        foreach ($rows as $row) {
            fputcsv($handle, [(string) $row->metric, (string) $row->period_start, (string) $row->period_end, (string) $row->value, (string) ($row->currency ?? ''), (string) ($row->source ?? '')], escape: '\\');
        }
        rewind($handle);
        $csv = stream_get_contents($handle);
        fclose($handle);

        return $csv;
    }
}
