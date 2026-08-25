<?php

declare(strict_types=1);

namespace Liberu\Billing\Usage\Livewire\Components;

use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;
use Liberu\Billing\Usage\Models\Meter;
use Liberu\Billing\Usage\Queries\AggregateUsage;
use Liberu\Billing\Usage\Queries\ListMeters;
use Livewire\Component;

final class UsageDashboard extends Component
{
    public ?int $meterId = null;

    public ?int $customerId = null;

    public function render(ListMeters $meters, AggregateUsage $aggregate): View
    {
        Gate::authorize('viewAny', Meter::class);
        $team = (int) (data_get(auth()->user(), 'current_team_id') ?? data_get(auth()->user(), 'currentTeam.id'));
        $available = $meters->execute($team);
        $selected = $this->meterId ? $available->firstWhere('id', $this->meterId) : $available->first();
        $summary = $selected ? $aggregate->execute((int) $selected->getKey(), $this->customerId) : null;

        return view('module-billing-usage-livewire::dashboard', ['meters' => $available, 'summary' => $summary]);
    }
}
