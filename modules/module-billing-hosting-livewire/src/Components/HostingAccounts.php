<?php

declare(strict_types=1);

namespace Liberu\Billing\Hosting\Livewire\Components;

use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;
use Liberu\Billing\Hosting\Actions\CreateHostingAccount;
use Liberu\Billing\Hosting\Actions\PerformHostingOperation;
use Liberu\Billing\Hosting\Actions\TransitionHostingAccount;
use Liberu\Billing\Hosting\Models\HostingAccount;
use Livewire\Component;

final class HostingAccounts extends Component
{
    public string $name = '';

    public string $status = 'active';

    public ?int $selectedAccountId = null;

    public string $operation = 'provision';

    public function createAccount(CreateHostingAccount $create): void
    {
        Gate::authorize('create', HostingAccount::class);
        $this->validate(['name' => ['required', 'string', 'max:255'], 'status' => ['required', 'in:pending,active,suspended,cancelled,failed']]);
        $create->handle($this->teamId(), ['name' => $this->name, 'status' => $this->status]);
        $this->reset(['name', 'selectedAccountId']);
        session()->flash('hosting-accounts-message', __('Hosting account created.'));
    }

    public function transitionAccount(TransitionHostingAccount $transition): void
    {
        $this->validate(['selectedAccountId' => ['required', 'integer', 'min:1'], 'status' => ['required', 'in:pending,active,suspended,cancelled,failed']]);
        $account = $this->account();
        Gate::authorize('update', $account);
        $transition->handle($account, $this->status);
        $this->reset('selectedAccountId');
        session()->flash('hosting-accounts-message', __('Hosting account status updated.'));
    }

    public function performOperation(PerformHostingOperation $perform): void
    {
        $this->validate(['selectedAccountId' => ['required', 'integer', 'min:1'], 'operation' => ['required', 'in:provision,suspend,unsuspend,change_package,terminate,add_addon,remove_addon']]);
        $account = $this->account();
        Gate::authorize('update', $account);
        $perform->handle($account, $this->operation);
        $this->reset('selectedAccountId');
        session()->flash('hosting-accounts-message', __('Hosting provider operation completed.'));
    }

    public function render(): View
    {
        Gate::authorize('viewAny', HostingAccount::class);

        return view('module-billing-hosting-livewire::accounts', [
            'accounts' => HostingAccount::query()->forTeam($this->teamId())->latest()->get(),
        ]);
    }

    private function account(): HostingAccount
    {
        return HostingAccount::query()->forTeam($this->teamId())->findOrFail($this->selectedAccountId);
    }

    private function teamId(): int
    {
        $team = data_get(auth()->user(), 'current_team_id') ?? data_get(auth()->user(), 'currentTeam.id');
        abort_if($team === null, 403, 'A current team is required.');

        return (int) $team;
    }
}
