<?php

declare(strict_types=1);

namespace Liberu\Billing\Communications\Livewire\Components;

use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;
use Liberu\Billing\Communications\Actions\ProvisionCommunicationNumber;
use Liberu\Billing\Communications\Actions\TransitionCommunicationNumber;
use Liberu\Billing\Communications\Models\CommunicationNumber;
use Livewire\Component;

final class CommunicationInventory extends Component
{
    public string $number = '';

    public string $type = 'phone';
    public ?int $selectedNumberId = null;
    public string $status = 'active';

    public function provision(ProvisionCommunicationNumber $provision): void
    {
        Gate::authorize('create', CommunicationNumber::class);
        $this->validate(['number' => ['required', 'string', 'max:64'], 'type' => ['required', 'string', 'max:32']]);
        $team = (int) (data_get(auth()->user(), 'current_team_id') ?? data_get(auth()->user(), 'currentTeam.id'));
        $provision->handle($team, ['number' => $this->number, 'type' => $this->type]);
        $this->reset('number');
        session()->flash('billing-communications-message', __('Communication number provisioned.'));
    }

    public function render(): View
    {
        Gate::authorize('viewAny', CommunicationNumber::class);
        $team = (int) (data_get(auth()->user(), 'current_team_id') ?? data_get(auth()->user(), 'currentTeam.id'));

        return view('billing-communications-livewire::communication-inventory', ['numbers' => CommunicationNumber::query()->where('team_id', $team)->latest()->get()]);
    }

    public function transition(TransitionCommunicationNumber $transition): void
    {
        $this->validate(['selectedNumberId' => ['required', 'integer'], 'status' => ['required', 'in:available,active,suspended,released,failed']]);
        $team = (int) (data_get(auth()->user(), 'current_team_id') ?? data_get(auth()->user(), 'currentTeam.id'));
        $number = CommunicationNumber::query()->whereKey($this->selectedNumberId)->where('team_id', $team)->firstOrFail();
        Gate::authorize('update', $number);
        $transition->handle($number, $this->status);
        session()->flash('billing-communications-message', __('Communication number status updated.'));
    }
}
