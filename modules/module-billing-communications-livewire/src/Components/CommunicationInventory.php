<?php

declare(strict_types=1);

namespace Liberu\Billing\Communications\Livewire\Components;

use Illuminate\View\View;
use Liberu\Billing\Communications\Models\CommunicationNumber;
use Illuminate\Support\Facades\Gate;
use Livewire\Component;

final class CommunicationInventory extends Component
{
    public function render(): View
    {
        Gate::authorize('viewAny', CommunicationNumber::class);
        $team = (int) (data_get(auth()->user(), 'current_team_id') ?? data_get(auth()->user(), 'currentTeam.id'));

        return view('billing-communications-livewire::communication-inventory', ['numbers' => CommunicationNumber::query()->where('team_id', $team)->latest()->get()]);
    }
}
