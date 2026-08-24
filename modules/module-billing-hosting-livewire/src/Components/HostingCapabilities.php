<?php

declare(strict_types=1);

namespace Liberu\Billing\Hosting\Livewire\Components;

use Illuminate\View\View;
use Liberu\Billing\Hosting\Models\HostingCapability;
use Illuminate\Support\Facades\Gate;
use Livewire\Component;

final class HostingCapabilities extends Component
{
    public function render(): View
    {
        Gate::authorize('viewAny', HostingCapability::class);
        $team = (int) (data_get(auth()->user(), 'current_team_id') ?? data_get(auth()->user(), 'currentTeam.id'));

        return view('module-billing-hosting-livewire::capabilities', ['capabilities' => HostingCapability::query()->where('team_id', $team)->latest()->get()]);
    }
}
