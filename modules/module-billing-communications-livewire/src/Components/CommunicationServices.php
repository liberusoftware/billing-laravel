<?php

declare(strict_types=1);

namespace Liberu\Billing\Communications\Livewire\Components;

use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;
use Liberu\Billing\Communications\Actions\CreateCommunicationService;
use Liberu\Billing\Communications\Actions\TransitionCommunicationService;
use Liberu\Billing\Communications\Models\CommunicationService;
use Livewire\Component;

final class CommunicationServices extends Component
{
    public string $name = '';

    public ?int $selectedServiceId = null;

    public string $status = 'active';

    public function createService(CreateCommunicationService $create): void
    {
        Gate::authorize('create', CommunicationService::class);
        $this->validate(['name' => ['required', 'string', 'max:255']]);
        $team = (int) (data_get(auth()->user(), 'current_team_id') ?? data_get(auth()->user(), 'currentTeam.id'));
        $create->handle($team, ['name' => $this->name]);
        $this->reset('name');
        session()->flash('billing-communications-services-message', __('Communication service created.'));
    }

    public function transition(TransitionCommunicationService $transition): void
    {
        $this->validate(['selectedServiceId' => ['required', 'integer'], 'status' => ['required', 'in:pending,active,suspended,cancelled,failed']]);
        $team = (int) (data_get(auth()->user(), 'current_team_id') ?? data_get(auth()->user(), 'currentTeam.id'));
        $service = CommunicationService::query()->forTeam($team)->findOrFail($this->selectedServiceId);
        Gate::authorize('update', $service);
        $transition->handle($service, $this->status);
        session()->flash('billing-communications-services-message', __('Communication service status updated.'));
    }

    public function render(): View
    {
        Gate::authorize('viewAny', CommunicationService::class);
        $team = (int) (data_get(auth()->user(), 'current_team_id') ?? data_get(auth()->user(), 'currentTeam.id'));

        return view('billing-communications-livewire::services', ['services' => CommunicationService::query()->forTeam($team)->latest()->get()]);
    }
}
