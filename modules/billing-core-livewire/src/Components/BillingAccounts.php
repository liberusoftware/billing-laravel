<?php

declare(strict_types=1);

namespace Liberu\Billing\Core\Livewire\Components;

use Illuminate\View\View;
use Liberu\Billing\Core\Actions\CreateBillingAccount;
use Liberu\Billing\Core\Queries\ListBillingAccounts;
use Livewire\Component;

final class BillingAccounts extends Component
{
    public string $name = '';

    public string $currency = 'USD';

    public bool $showCreate = false;

    public function save(CreateBillingAccount $create): void
    {
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
        $teamId = data_get(auth()->user(), 'current_team_id') ?? data_get(auth()->user(), 'currentTeam.id');

        return view('billing-core-livewire::billing-accounts', [
            'accounts' => $query->execute($teamId !== null ? (int) $teamId : null),
        ]);
    }
}
