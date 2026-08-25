<?php

declare(strict_types=1);

namespace Liberu\Billing\CustomerPortal\Livewire\Components;

use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;
use Liberu\Billing\CustomerPortal\Models\PortalItem;
use Liberu\Billing\CustomerPortal\Queries\ListPortalItems;
use Livewire\Component;
use Livewire\WithPagination;

final class PortalDashboard extends Component
{
    use WithPagination;

    public string $type = '';
    public int $perPage = 25;

    public function updatedType(): void
    {
        $this->resetPage();
    }

    public function render(ListPortalItems $items): View
    {
        Gate::authorize('viewAny', PortalItem::class);
        $team = (int) (data_get(auth()->user(), 'current_team_id') ?? data_get(auth()->user(), 'currentTeam.id'));

        return view('billing-customer-portal-livewire::dashboard', ['items' => $items->handle($team, $this->type !== '' ? $this->type : null)]);
    }
}
