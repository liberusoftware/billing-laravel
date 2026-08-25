<?php

declare(strict_types=1);

namespace Liberu\Billing\Reporting\Livewire\Components;

use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;
use Liberu\Billing\Reporting\Actions\CalculateReportingMetric;
use Liberu\Billing\Reporting\Actions\RecordReportingMetric;
use Liberu\Billing\Reporting\Models\ReportingMetric;
use Liberu\Billing\Reporting\Queries\ListReportingMetrics;
use Livewire\Component;
use Livewire\WithPagination;

final class ReportingDashboard extends Component
{
    use WithPagination;

    public string $metric = '';

    public int $perPage = 25;

    public string $periodStart = '';

    public string $periodEnd = '';

    public string $currency = 'USD';

    public function calculate(CalculateReportingMetric $calculator, RecordReportingMetric $record): void
    {
        Gate::authorize('create', ReportingMetric::class);
        $this->validate(['metric' => ['required', 'in:mrr,arr,churn,aging,revenue,tax,usage,provisioning,collection,provider'], 'periodStart' => ['required', 'date'], 'periodEnd' => ['required', 'date', 'after_or_equal:periodStart'], 'currency' => ['nullable', 'string', 'size:3', 'alpha']]);
        $team = $this->teamId();
        $data = $calculator->execute($team, $this->metric, CarbonImmutable::parse($this->periodStart), CarbonImmutable::parse($this->periodEnd), $this->currency ?: null);
        $record->execute($team, $data);
        session()->flash('module-billing-reporting-message', __('Metric calculated.'));
        $this->resetPage();
    }

    public function updatedMetric(): void
    {
        $this->resetPage();
    }

    public function render(ListReportingMetrics $metrics): View
    {
        Gate::authorize('viewAny', ReportingMetric::class);
        $team = $this->teamId();

        return view('module-billing-reporting-livewire::dashboard', ['metrics' => $metrics->execute($team, $this->metric !== '' ? $this->metric : null, $this->perPage)]);
    }

    private function teamId(): int
    {
        $team = data_get(auth()->user(), 'current_team_id') ?? data_get(auth()->user(), 'currentTeam.id');
        abort_if($team === null, 403, 'A current team is required.');

        return (int) $team;
    }
}
