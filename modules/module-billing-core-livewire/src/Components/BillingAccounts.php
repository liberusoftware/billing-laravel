<?php

declare(strict_types=1);

namespace Liberu\Billing\Core\Livewire\Components;

use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;
use Liberu\Billing\Core\Actions\CreateBillingAccount;
use Liberu\Billing\Core\Actions\TransitionBillingAccount;
use Liberu\Billing\Core\Actions\UpdateBillingAccount;
use Liberu\Billing\Core\Enums\BillingAccountStatus;
use Liberu\Billing\Core\Models\BillingAccount;
use Liberu\Billing\Core\Queries\ListBillingAccounts;
use Livewire\Component;

final class BillingAccounts extends Component
{
    public string $name = '';

    public string $currency = 'USD';

    public bool $showCreate = false;

    public ?int $selectedAccountId = null;

    public string $status = 'active';

    public function save(CreateBillingAccount $create): void
    {
        Gate::authorize('create', BillingAccount::class);
        $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'currency' => ['required', 'string', 'size:3', 'alpha'],
        ]);

        $teamId = data_get(auth()->user(), 'current_team_id') ?? data_get(auth()->user(), 'currentTeam.id');
        $create->execute(['name' => $this->name, 'currency' => $this->currency, 'team_id' => $teamId]);
        $this->reset(['name']);
        $this->currency = 'USD';
        $this->showCreate = false;
        session()->flash('billing-core-message', __('Billing account created.'));
    }

    public function render(ListBillingAccounts $query): View
    {
        Gate::authorize('viewAny', BillingAccount::class);
        $teamId = data_get(auth()->user(), 'current_team_id') ?? data_get(auth()->user(), 'currentTeam.id');

        return view('module-billing-core-livewire::billing-accounts', [
            'accounts' => $query->execute($teamId !== null ? (int) $teamId : null),
        ]);
    }

    public function updateAccount(UpdateBillingAccount $update): void
    {
        $account = $this->accountForCurrentTeam();
        Gate::authorize('update', $account);
        $this->validate(['name' => ['required', 'string', 'max:255'], 'currency' => ['required', 'string', 'size:3', 'alpha']]);
        $update->execute($account, ['name' => $this->name, 'currency' => $this->currency]);
        session()->flash('billing-core-message', __('Billing account updated.'));
    }

    public function transitionAccount(TransitionBillingAccount $transition): void
    {
        $account = $this->accountForCurrentTeam();
        Gate::authorize('update', $account);
        $this->validate(['status' => ['required', 'in:active,suspended,closed']]);
        $transition->execute($account, BillingAccountStatus::from($this->status));
        session()->flash('billing-core-message', __('Billing account status updated.'));
    }

    private function accountForCurrentTeam(): BillingAccount
    {
        $teamId = data_get(auth()->user(), 'current_team_id') ?? data_get(auth()->user(), 'currentTeam.id');

        return BillingAccount::query()->whereKey($this->selectedAccountId)->where('team_id', $teamId)->firstOrFail();
    }
}
