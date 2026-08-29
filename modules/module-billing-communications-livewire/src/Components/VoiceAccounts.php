<?php

declare(strict_types=1);

namespace Liberu\Billing\Communications\Livewire\Components;

use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;
use Liberu\Billing\Communications\Actions\CreateVoipAccount;
use Liberu\Billing\Communications\Actions\ProvisionVoipAccount;
use Liberu\Billing\Communications\Models\VoipAccount;
use Livewire\Component;

final class VoiceAccounts extends Component
{
    public string $platform = '';

    public string $customerId = '';

    public string $sipUsername = '';

    public string $sipSecret = '';

    public function create(CreateVoipAccount $create): void
    {
        Gate::authorize('create', VoipAccount::class);
        $this->validate(['customerId' => ['required', 'integer', 'min:1'], 'platform' => ['required', 'string', 'max:100'], 'sipUsername' => ['required', 'string', 'max:255'], 'sipSecret' => ['required', 'string', 'max:1000']]);
        $team = data_get(auth()->user(), 'current_team_id') ?? data_get(auth()->user(), 'currentTeam.id');
        abort_if($team === null, 403, 'A current team is required.');
        $create->handle((int) $team, ['customer_id' => (int) $this->customerId, 'platform' => $this->platform, 'sip_username' => $this->sipUsername, 'sip_secret' => $this->sipSecret]);
        $this->reset(['customerId', 'platform', 'sipUsername', 'sipSecret']);
        session()->flash('billing-communications-message', __('Voice account created.'));
    }

    public function provision(int $account, ProvisionVoipAccount $provision): void
    {
        $team = data_get(auth()->user(), 'current_team_id') ?? data_get(auth()->user(), 'currentTeam.id');
        abort_if($team === null, 403, 'A current team is required.');
        $model = VoipAccount::query()->forTeam((int) $team)->findOrFail($account);
        Gate::authorize('update', $model);
        $provision->handle($model);
        session()->flash('billing-communications-message', __('Voice account provisioned.'));
    }

    public function render(): View
    {
        Gate::authorize('viewAny', VoipAccount::class);
        $team = data_get(auth()->user(), 'current_team_id') ?? data_get(auth()->user(), 'currentTeam.id');
        abort_if($team === null, 403, 'A current team is required.');

        return view('billing-communications-livewire::voice-accounts', ['accounts' => VoipAccount::query()->forTeam((int) $team)->latest()->get()]);
    }
}
