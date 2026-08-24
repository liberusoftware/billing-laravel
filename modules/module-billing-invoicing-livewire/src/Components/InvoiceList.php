<?php

declare(strict_types=1);

namespace Liberu\Billing\Invoicing\Livewire\Components;

use Illuminate\View\View;
use Liberu\Billing\Invoicing\Actions\CreateInvoice;
use Liberu\Billing\Invoicing\Queries\ListInvoices;
use Livewire\Component;

final class InvoiceList extends Component
{
    public string $currency = 'USD';

    public bool $showCreate = false;

    public function create(CreateInvoice $create): void
    {
        $this->validate(['currency' => ['required', 'string', 'size:3', 'alpha']]);
        $teamId = data_get(auth()->user(), 'current_team_id') ?? data_get(auth()->user(), 'currentTeam.id');
        $create->execute(['team_id' => $teamId, 'currency' => $this->currency]);
        $this->reset(['showCreate']);
        session()->flash('module-billing-invoicing-message', __('Draft invoice created.'));
    }

    public function render(ListInvoices $query): View
    {
        $teamId = data_get(auth()->user(), 'current_team_id') ?? data_get(auth()->user(), 'currentTeam.id');

        return view('module-billing-invoicing-livewire::invoice-list', ['invoices' => $query->execute($teamId === null ? null : (int) $teamId)]);
    }
}
