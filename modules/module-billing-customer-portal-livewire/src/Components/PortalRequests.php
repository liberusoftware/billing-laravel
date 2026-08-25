<?php

declare(strict_types=1);

namespace Liberu\Billing\CustomerPortal\Livewire\Components;

use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;
use Liberu\Billing\CustomerPortal\Actions\CreatePortalRequest;
use Liberu\Billing\CustomerPortal\Actions\TransitionPortalRequest;
use Liberu\Billing\CustomerPortal\Models\PortalRequest;
use Livewire\Component;

final class PortalRequests extends Component
{
    public string $name = '';

    public string $status = 'active';

    public ?int $selectedRequestId = null;

    public function createRequest(CreatePortalRequest $create): void
    {
        Gate::authorize('create', PortalRequest::class);
        $this->validate(['name' => ['required', 'string', 'max:255'], 'status' => ['required', 'in:active,closed,failed']]);
        $create->handle($this->teamId(), ['name' => $this->name, 'status' => $this->status]);
        $this->reset(['name', 'selectedRequestId']);
        session()->flash('billing-customer-portal-requests-message', __('Portal request created.'));
    }

    public function transition(TransitionPortalRequest $transition): void
    {
        $this->validate(['selectedRequestId' => ['required', 'integer', 'min:1'], 'status' => ['required', 'in:active,closed,failed']]);
        $request = $this->requestForCurrentTeam();
        Gate::authorize('update', $request);
        $transition->handle($request, $this->status);
        $this->reset('selectedRequestId');
        session()->flash('billing-customer-portal-requests-message', __('Portal request status updated.'));
    }

    public function render(): View
    {
        Gate::authorize('viewAny', PortalRequest::class);

        return view('billing-customer-portal-livewire::requests', ['requests' => PortalRequest::query()->forTeam($this->teamId())->latest()->get()]);
    }

    private function requestForCurrentTeam(): PortalRequest
    {
        return PortalRequest::query()->forTeam($this->teamId())->findOrFail($this->selectedRequestId);
    }

    private function teamId(): int
    {
        return (int) (data_get(auth()->user(), 'current_team_id') ?? data_get(auth()->user(), 'currentTeam.id'));
    }
}
