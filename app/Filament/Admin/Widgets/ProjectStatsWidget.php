<?php

declare(strict_types=1);

namespace App\Filament\Admin\Widgets;

use App\Services\ProjectReportService;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class ProjectStatsWidget extends StatsOverviewWidget
{
    /**
     * @return array<Stat>
     */
    protected function getStats(): array
    {
        $report = new ProjectReportService;

        $byStatus = $report->projectCountByStatus();
        $split = $report->billableSplit();

        return [
            Stat::make('Projects', (string) array_sum($byStatus))
                ->description('Total tracked projects'),
            Stat::make('Billable hours', $this->toHours($split['billable']))
                ->description('Logged as billable')
                ->color('success'),
            Stat::make('Non-billable hours', $this->toHours($split['non_billable']))
                ->description('Logged as non-billable')
                ->color('gray'),
        ];
    }

    private function toHours(int $seconds): string
    {
        return number_format($seconds / 3600, 1);
    }
}
