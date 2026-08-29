<?php

declare(strict_types=1);

namespace Liberu\Billing\Invoicing\Livewire\Components;

use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;
use Liberu\Billing\Invoicing\Actions\ApplyInvoiceAdjustment;
use Liberu\Billing\Invoicing\Actions\CreateInvoice;
use Liberu\Billing\Invoicing\Actions\DeliverInvoice;
use Liberu\Billing\Invoicing\Actions\FinalizeInvoice;
use Liberu\Billing\Invoicing\Actions\GenerateInvoiceDocument;
use Liberu\Billing\Invoicing\Models\Invoice;
use Liberu\Billing\Invoicing\Queries\ListInvoices;
use Livewire\Component;

final class InvoiceList extends Component
{
    public string $currency = 'USD';

    public bool $showCreate = false;

    public ?int $selectedInvoiceId = null;

    public int $adjustmentMinor = 0;

    public string $adjustmentReason = '';

    public string $deliveryDestination = '';

    public function finalize(int $invoiceId, FinalizeInvoice $finalize): void
    {
        $finalize->execute($this->authorizedInvoice($invoiceId));
        session()->flash('module-billing-invoicing-message', __('Invoice finalized.'));
    }

    public function document(int $invoiceId, GenerateInvoiceDocument $document): void
    {
        $document->execute($this->authorizedInvoice($invoiceId));
        session()->flash('module-billing-invoicing-message', __('Invoice document generated.'));
    }

    public function deliver(int $invoiceId, DeliverInvoice $deliver): void
    {
        $this->validate(['deliveryDestination' => ['required', 'email', 'max:255']]);
        $deliver->execute($this->authorizedInvoice($invoiceId), $this->deliveryDestination);
        $this->reset('deliveryDestination');
        session()->flash('module-billing-invoicing-message', __('Invoice delivered.'));
    }

    public function adjust(ApplyInvoiceAdjustment $adjust): void
    {
        $this->validate(['selectedInvoiceId' => ['required', 'integer', 'min:1'], 'adjustmentMinor' => ['required', 'integer', 'not_in:0'], 'adjustmentReason' => ['required', 'string', 'max:1000']]);
        $invoice = $this->authorizedInvoice($this->selectedInvoiceId);
        $adjust->execute($invoice, $this->adjustmentMinor, $this->adjustmentReason);
        $this->reset(['selectedInvoiceId', 'adjustmentMinor', 'adjustmentReason']);
        session()->flash('module-billing-invoicing-message', __('Invoice adjustment applied.'));
    }

    public function create(CreateInvoice $create): void
    {
        Gate::authorize('create', Invoice::class);
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

    private function authorizedInvoice(int $invoiceId): Invoice
    {
        $teamId = data_get(auth()->user(), 'current_team_id') ?? data_get(auth()->user(), 'currentTeam.id');
        $invoice = Invoice::query()->whereKey($invoiceId)->where('team_id', $teamId)->firstOrFail();
        Gate::authorize('update', $invoice);

        return $invoice;
    }
}
