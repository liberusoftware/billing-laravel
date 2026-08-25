<?php

declare(strict_types=1);

namespace Liberu\Billing\Isp\Livewire\Components;

use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;
use Liberu\Billing\Isp\Actions\CreateAccessService;
use Liberu\Billing\Isp\Actions\TransitionAccessService;
use Liberu\Billing\Isp\Models\AccessService;
use Livewire\Component;

final class AccessServices extends Component
{
    public string $name = '';

    public ?int $selectedServiceId = null;

    public string $status = 'active';

    public function createService(CreateAccessService $create): void
    {
        Gate::authorize('create', AccessService::class);
        $this->validate(['name' => ['required', 'string', 'max:255']]);
        $team = (int) (data_get(auth()->user(), 'current_team_id') ?? data_get(auth()->user(), 'currentTeam.id'));
        $create->handle($team, ['name' => $this->name]);
        $this->reset('name');
        session()->flash('isp-services-message', __('ISP access service created.'));
    }

    public function transitionService(TransitionAccessService $transition): void
    {
        $this->validate(['selectedServiceId' => ['required', 'integer'], 'status' => ['required', 'in:pending,active,suspended,cancelled,failed']]);
        $team = (int) (data_get(auth()->user(), 'current_team_id') ?? data_get(auth()->user(), 'currentTeam.id'));
        $service = AccessService::query()->forTeam($team)->findOrFail($this->selectedServiceId);
        Gate::authorize('update', $service);
        $transition->handle($service, $this->status);
        session()->flash('isp-services-message', __('ISP access service status updated.'));
    }

    public function render(): View
    {
        Gate::authorize('viewAny', AccessService::class);
        $team = (int) (data_get(auth()->user(), 'current_team_id') ?? data_get(auth()->user(), 'currentTeam.id'));

        return view('module-billing-isp-livewire::services', ['services' => AccessService::query()->forTeam($team)->latest()->get()]);
    }
}
