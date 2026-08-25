<?php

declare(strict_types=1);

namespace Liberu\Billing\CustomerPortal\Livewire\Components;

use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;
use Liberu\Billing\CustomerPortal\Actions\CreatePortalItem;
use Liberu\Billing\CustomerPortal\Actions\TransitionPortalItem;
use Liberu\Billing\CustomerPortal\Models\PortalItem;
use Livewire\Component;

final class PortalItems extends Component
{
    public string $type = 'profile';

    public string $subject = '';

    public ?int $customerId = null;

    public ?int $selectedItemId = null;

    public string $status = 'open';

    public function createItem(CreatePortalItem $create): void
    {
        Gate::authorize('create', PortalItem::class);
        $this->validate([
            'type' => ['required', 'in:profile,orders,services,usage,invoices,payments,tickets,changes,cancellation'],
            'subject' => ['required', 'string', 'max:255'],
            'customerId' => ['nullable', 'integer', 'min:1'],
        ]);
        $team = $this->teamId();
        $create->handle($team, ['type' => $this->type, 'subject' => $this->subject, 'customer_id' => $this->customerId]);
        $this->reset('subject', 'customerId');
        session()->flash('billing-customer-portal-message', __('Portal item created.'));
    }

    public function render(): View
    {
        Gate::authorize('viewAny', PortalItem::class);
        $team = $this->teamId();

        return view('billing-customer-portal-livewire::portal-items', ['items' => PortalItem::query()->where('team_id', $team)->latest()->get()]);
    }

    public function transitionItem(TransitionPortalItem $transition): void
    {
        $this->validate(['selectedItemId' => ['required', 'integer'], 'status' => ['required', 'in:open,in_progress,completed,cancelled,failed']]);
        $team = $this->teamId();
        $item = PortalItem::query()->whereKey($this->selectedItemId)->where('team_id', $team)->firstOrFail();
        Gate::authorize('update', $item);
        $transition->handle($item, $this->status);
        session()->flash('billing-customer-portal-message', __('Portal item status updated.'));
    }

    private function teamId(): int
    {
        $team = data_get(auth()->user(), 'current_team_id') ?? data_get(auth()->user(), 'currentTeam.id');
        abort_if($team === null, 403, 'A current team is required.');

        return (int) $team;
    }
}
