<?php

declare(strict_types=1);

namespace Liberu\Billing\Hosting\Livewire\Components;

use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;
use Liberu\Billing\Hosting\Actions\CreateHostingCapability;
use Liberu\Billing\Hosting\Actions\TransitionHostingCapability;
use Liberu\Billing\Hosting\Models\HostingCapability;
use Livewire\Component;

final class HostingCapabilities extends Component
{
    public string $type = 'plan';

    public string $name = '';

    public string $status = 'active';

    public function transitionCapability(int $capabilityId, TransitionHostingCapability $transition): void
    {
        $this->validate(['status' => ['required', 'in:pending,active,suspended,cancelled,failed']]);
        $team = $this->teamId();
        $capability = HostingCapability::query()->where('team_id', $team)->findOrFail($capabilityId);
        Gate::authorize('update', $capability);
        $transition->handle($capability, $this->status);
        session()->flash('hosting-capabilities-message', __('Hosting capability status updated.'));
    }

    public function createCapability(CreateHostingCapability $create): void
    {
        Gate::authorize('create', HostingCapability::class);
        $this->validate(['type' => ['required', 'in:plan,control_panel,ssl,resource,lifecycle'], 'name' => ['required', 'string', 'max:255']]);
        $team = $this->teamId();
        $create->handle($team, ['type' => $this->type, 'name' => $this->name]);
        $this->reset('name');
        session()->flash('hosting-capabilities-message', __('Hosting capability created.'));
    }

    public function render(): View
    {
        Gate::authorize('viewAny', HostingCapability::class);
        $team = $this->teamId();

        return view('module-billing-hosting-livewire::capabilities', ['capabilities' => HostingCapability::query()->where('team_id', $team)->latest()->get()]);
    }

    private function teamId(): int
    {
        $team = data_get(auth()->user(), 'current_team_id') ?? data_get(auth()->user(), 'currentTeam.id');
        abort_if($team === null, 403, 'A current team is required.');

        return (int) $team;
    }
}
