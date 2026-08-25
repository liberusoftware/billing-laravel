<?php

declare(strict_types=1);

namespace Liberu\Billing\Isp\Livewire\Components;

use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;
use Liberu\Billing\Isp\Actions\CreateIspCapability;
use Liberu\Billing\Isp\Actions\TransitionIspCapability;
use Liberu\Billing\Isp\Models\IspCapability;
use Livewire\Component;

final class IspCapabilities extends Component
{
    public string $type = 'coverage';

    public string $name = '';

    public ?int $selectedCapabilityId = null;

    public string $status = 'active';

    public function createCapability(CreateIspCapability $create): void
    {
        Gate::authorize('create', IspCapability::class);
        $this->validate(['type' => ['required', 'in:coverage,installation,radius,usage,equipment,network_adapter'], 'name' => ['required', 'string', 'max:255']]);
        $team = (int) (data_get(auth()->user(), 'current_team_id') ?? data_get(auth()->user(), 'currentTeam.id'));
        $create->handle($team, ['type' => $this->type, 'name' => $this->name]);
        $this->reset('name');
        session()->flash('isp-capabilities-message', __('ISP capability created.'));
    }

    public function render(): View
    {
        Gate::authorize('viewAny', IspCapability::class);
        $team = (int) (data_get(auth()->user(), 'current_team_id') ?? data_get(auth()->user(), 'currentTeam.id'));

        return view('module-billing-isp-livewire::capabilities', ['capabilities' => IspCapability::query()->where('team_id', $team)->latest()->get()]);
    }

    public function transition(TransitionIspCapability $transition): void
    {
        $this->validate(['selectedCapabilityId' => ['required', 'integer'], 'status' => ['required', 'in:pending,active,suspended,cancelled,failed']]);
        $team = (int) (data_get(auth()->user(), 'current_team_id') ?? data_get(auth()->user(), 'currentTeam.id'));
        $capability = IspCapability::query()->whereKey($this->selectedCapabilityId)->where('team_id', $team)->firstOrFail();
        Gate::authorize('update', $capability);
        $transition->handle($capability, $this->status);
        session()->flash('isp-capabilities-message', __('ISP capability status updated.'));
    }
}
