<?php

declare(strict_types=1);

namespace Liberu\Billing\Invoicing\Livewire\Components;

use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;
use Liberu\Billing\Invoicing\Actions\AddInvoiceLine;
use Liberu\Billing\Invoicing\Actions\CreateInvoiceSupport;
use Liberu\Billing\Invoicing\Models\Invoice;
use Liberu\Billing\Invoicing\Models\InvoiceSupport;
use Livewire\Component;

final class InvoiceSupportList extends Component
{
    public ?int $selectedInvoiceId = null;

    public string $description = '';

    public int $quantity = 1;

    public int $unitAmountMinor = 0;

    public float $taxRate = 0;

    public string $supportType = 'tax';

    public int $supportAmountMinor = 0;

    public string $supportCurrency = 'USD';

    public string $supportDestination = '';

    public function addLine(AddInvoiceLine $add): void
    {
        Gate::authorize('update', $this->invoice());
        $this->validate(['description' => ['required', 'string', 'max:255'], 'quantity' => ['required', 'integer', 'min:1'], 'unitAmountMinor' => ['required', 'integer', 'min:0'], 'taxRate' => ['required', 'numeric', 'min:0', 'max:100']]);
        $add->execute($this->invoice(), $this->description, $this->quantity, $this->unitAmountMinor, $this->taxRate);
        $this->reset(['description', 'quantity', 'unitAmountMinor', 'taxRate']);
        $this->quantity = 1;
        session()->flash('module-billing-invoicing-support-message', __('Invoice line added.'));
    }

    public function createSupport(CreateInvoiceSupport $create): void
    {
        Gate::authorize('create', InvoiceSupport::class);
        $this->validate(['supportType' => ['required', 'in:tax,credit,adjustment,pdf,delivery'], 'supportAmountMinor' => ['required', 'integer', 'min:0'], 'supportCurrency' => ['nullable', 'string', 'size:3', 'alpha'], 'supportDestination' => ['nullable', 'string', 'max:255']]);
        $create->execute($this->teamId(), ['invoice_id' => $this->selectedInvoiceId, 'type' => $this->supportType, 'amount_minor' => $this->supportAmountMinor, 'currency' => $this->supportCurrency ?: null, 'destination' => $this->supportDestination ?: null]);
        $this->reset(['supportAmountMinor', 'supportDestination']);
        session()->flash('module-billing-invoicing-support-message', __('Invoice support record created.'));
    }

    public function render(): View
    {
        Gate::authorize('viewAny', InvoiceSupport::class);
        $team = $this->teamId();

        return view('module-billing-invoicing-livewire::support-list', ['items' => InvoiceSupport::query()->where('team_id', $team)->latest()->get(), 'invoices' => Invoice::query()->where('team_id', $team)->latest()->get()]);
    }

    private function invoice(): Invoice
    {
        return Invoice::query()->whereKey($this->selectedInvoiceId)->where('team_id', $this->teamId())->firstOrFail();
    }

    private function teamId(): int
    {
        return (int) (data_get(auth()->user(), 'current_team_id') ?? data_get(auth()->user(), 'currentTeam.id'));
    }
}
