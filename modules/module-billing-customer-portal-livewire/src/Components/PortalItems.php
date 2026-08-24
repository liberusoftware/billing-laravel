<?php

declare(strict_types=1);

namespace Liberu\Billing\CustomerPortal\Livewire\Components;

use Illuminate\View\View;
use Liberu\Billing\CustomerPortal\Models\PortalItem;
use Illuminate\Support\Facades\Gate;
use Livewire\Component;

final class PortalItems extends Component
{
    public function render(): View
    {
        Gate::authorize('viewAny', PortalItem::class);
        $team = (int) (data_get(auth()->user(), 'current_team_id') ?? data_get(auth()->user(), 'currentTeam.id'));

        return view('billing-customer-portal-livewire::portal-items', ['items' => PortalItem::query()->where('team_id', $team)->latest()->get()]);
    }
}
