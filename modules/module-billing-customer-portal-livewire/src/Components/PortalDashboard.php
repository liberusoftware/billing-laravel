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
        $team = $this->teamId();

        return view('billing-customer-portal-livewire::dashboard', ['items' => $items->handle($team, $this->type !== '' ? $this->type : null)]);
    }

    private function teamId(): int
    {
        $team = data_get(auth()->user(), 'current_team_id') ?? data_get(auth()->user(), 'currentTeam.id');
        abort_if($team === null, 403, 'A current team is required.');

        return (int) $team;
    }
}
