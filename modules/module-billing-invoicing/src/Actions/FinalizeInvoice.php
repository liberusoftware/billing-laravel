<?php

declare(strict_types=1);

namespace Liberu\Billing\Invoicing\Actions;

use Illuminate\Database\DatabaseManager;
use Liberu\Billing\Invoicing\Enums\InvoiceStatus;
use Liberu\Billing\Invoicing\Models\Invoice;

final readonly class FinalizeInvoice
{
    public function __construct(private DatabaseManager $database) {}

    public function execute(Invoice $invoice): Invoice
    {
        if ($invoice->status !== InvoiceStatus::Draft || ! $invoice->lines()->exists()) {
            throw new \LogicException('Only non-empty draft invoices can be finalized.');
        }

        return $this->database->transaction(function () use ($invoice): Invoice {
            $invoice = Invoice::query()->lockForUpdate()->findOrFail($invoice->getKey());
            if ($invoice->status !== InvoiceStatus::Draft || ! $invoice->lines()->exists()) {
                throw new \LogicException('Only non-empty draft invoices can be finalized.');
            }
            $invoice->update(['status' => InvoiceStatus::Finalized, 'finalized_at' => now()]);

            return $invoice->refresh();
        });
    }
}
