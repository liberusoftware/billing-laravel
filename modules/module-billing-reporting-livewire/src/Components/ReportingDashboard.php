<?php

declare(strict_types=1);

namespace Liberu\Billing\Reporting\Livewire\Components;

use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;
use Liberu\Billing\Reporting\Models\ReportingMetric;
use Liberu\Billing\Reporting\Queries\ListReportingMetrics;
use Livewire\Component;
use Livewire\WithPagination;

final class ReportingDashboard extends Component
{
    use WithPagination;

    public string $metric = '';
    public int $perPage = 25;

    public function updatedMetric(): void
    {
        $this->resetPage();
    }

    public function render(ListReportingMetrics $metrics): View
    {
        Gate::authorize('viewAny', ReportingMetric::class);
        $team = (int) (data_get(auth()->user(), 'current_team_id') ?? data_get(auth()->user(), 'currentTeam.id'));

        return view('module-billing-reporting-livewire::dashboard', ['metrics' => $metrics->execute($team, $this->metric !== '' ? $this->metric : null, $this->perPage)]);
    }
}
