<?php

declare(strict_types=1);

namespace Liberu\Billing\Isp\Livewire\Components;

use Illuminate\View\View;
use Liberu\Billing\Isp\Models\IspCapability;
use Illuminate\Support\Facades\Gate;
use Livewire\Component;

final class IspCapabilities extends Component
{
    public function render(): View
    {
        Gate::authorize('viewAny', IspCapability::class);
        $team = (int) (data_get(auth()->user(), 'current_team_id') ?? data_get(auth()->user(), 'currentTeam.id'));

        return view('module-billing-isp-livewire::capabilities', ['capabilities' => IspCapability::query()->where('team_id', $team)->latest()->get()]);
    }
}
