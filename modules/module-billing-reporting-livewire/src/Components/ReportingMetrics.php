<?php

declare(strict_types=1);

namespace Liberu\Billing\Reporting\Livewire\Components;

use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;
use Liberu\Billing\Reporting\Actions\ExportReportingMetrics;
use Liberu\Billing\Reporting\Models\ReportingMetric;
use Livewire\Component;
use Symfony\Component\HttpFoundation\StreamedResponse;

final class ReportingMetrics extends Component
{
    public function export(ExportReportingMetrics $export): StreamedResponse
    {
        Gate::authorize('viewAny', ReportingMetric::class);
        $team = data_get(auth()->user(), 'current_team_id') ?? data_get(auth()->user(), 'currentTeam.id');
        abort_if($team === null, 403, 'A current team is required.');
        $csv = $export->execute((int) $team);

        return response()->streamDownload(static function () use ($csv): void {
            echo $csv;
        }, 'billing-reporting-metrics.csv', ['Content-Type' => 'text/csv']);
    }

    public function render(): View
    {
        Gate::authorize('viewAny', ReportingMetric::class);
        $team = data_get(auth()->user(), 'current_team_id') ?? data_get(auth()->user(), 'currentTeam.id');
        abort_if($team === null, 403, 'A current team is required.');
        $team = (int) $team;

        return view('module-billing-reporting-livewire::metrics', ['metrics' => ReportingMetric::query()->where('team_id', $team)->latest('period_end')->get()]);
    }
}
