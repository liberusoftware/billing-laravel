<?php

declare(strict_types=1);

namespace Liberu\Billing\Reporting\Livewire\Components;

use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;
use Liberu\Billing\Reporting\Actions\CreateMetricSnapshot;
use Liberu\Billing\Reporting\Models\MetricSnapshot;
use Livewire\Component;

final class ReportingSnapshots extends Component
{
    public string $name = '';

    public function createSnapshot(CreateMetricSnapshot $create): void
    {
        Gate::authorize('create', MetricSnapshot::class);
        $this->validate(['name' => ['required', 'string', 'max:255']]);
        $team = data_get(auth()->user(), 'current_team_id') ?? data_get(auth()->user(), 'currentTeam.id');
        $create->handle((int) $team, ['name' => $this->name]);
        $this->reset('name');
        session()->flash('module-billing-reporting-snapshot-message', __('Metric snapshot created.'));
    }

    public function render(): View
    {
        Gate::authorize('viewAny', MetricSnapshot::class);
        $team = data_get(auth()->user(), 'current_team_id') ?? data_get(auth()->user(), 'currentTeam.id');

        return view('module-billing-reporting-livewire::snapshots', ['snapshots' => MetricSnapshot::query()->where('team_id', $team)->latest()->get()]);
    }
}
