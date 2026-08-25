<?php

declare(strict_types=1);

namespace Liberu\Billing\Reporting\Livewire\Components;

use Illuminate\View\View;
use Liberu\Billing\Reporting\Models\ReportingMetric;
use Illuminate\Support\Facades\Gate;
use Livewire\Component;

final class ReportingMetrics extends Component
{
    public function render(): View
    {
        Gate::authorize('viewAny', ReportingMetric::class);
        $team = (int) (data_get(auth()->user(), 'current_team_id') ?? data_get(auth()->user(), 'currentTeam.id'));

        return view('module-billing-reporting-livewire::metrics', ['metrics' => ReportingMetric::query()->where('team_id', $team)->latest('period_end')->get()]);
    }
}
