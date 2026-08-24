<?php

declare(strict_types=1);

namespace Liberu\Billing\Usage\Livewire\Components;

use Illuminate\View\View;
use Liberu\Billing\Usage\Models\UsageRecord;
use Illuminate\Support\Facades\Gate;
use Livewire\Component;

final class UsageRecords extends Component
{
    public function render(): View
    {
        Gate::authorize('viewAny', UsageRecord::class);
        $team = (int) (data_get(auth()->user(), 'current_team_id') ?? data_get(auth()->user(), 'currentTeam.id'));

        return view('module-billing-usage-livewire::records', ['records' => UsageRecord::query()->where('team_id', $team)->latest('occurred_at')->get()]);
    }
}
