<?php

declare(strict_types=1);

namespace Liberu\Billing\Invoicing\Actions;

use Illuminate\Database\DatabaseManager;
use Liberu\Billing\Invoicing\Enums\InvoiceStatus;
use Liberu\Billing\Invoicing\Models\Invoice;
use Liberu\Billing\Invoicing\Models\InvoiceLine;

final readonly class AddInvoiceLine
{
    public function __construct(private DatabaseManager $database) {}

    public function execute(Invoice $invoice, string $description, int $quantity, int $unitAmountMinor, float $taxRate = 0): InvoiceLine
    {
        if ($invoice->status !== InvoiceStatus::Draft || trim($description) === '' || $quantity < 1 || $unitAmountMinor < 0 || $taxRate < 0 || $taxRate > 100) {
            throw new \InvalidArgumentException('Invoice line is invalid or invoice is not editable.');
        }

        return $this->database->transaction(function () use ($invoice, $description, $quantity, $unitAmountMinor, $taxRate): InvoiceLine {
            $invoice = Invoice::query()->lockForUpdate()->findOrFail($invoice->getKey());
            if ($invoice->status !== InvoiceStatus::Draft) {
                throw new \LogicException('Only draft invoices can be changed.');
            }
            $amount = $quantity * $unitAmountMinor;
            $line = InvoiceLine::query()->create(['invoice_id' => $invoice->getKey(), 'description' => trim($description), 'quantity' => $quantity, 'unit_amount_minor' => $unitAmountMinor, 'tax_rate' => $taxRate, 'amount_minor' => $amount]);
            $subtotal = (int) $invoice->lines()->sum('amount_minor');
            $tax = (int) $invoice->lines()->get()->sum(fn (InvoiceLine $invoiceLine): int => (int) round($invoiceLine->amount_minor * (float) $invoiceLine->tax_rate / 100));
            $invoice->update(['subtotal_minor' => $subtotal, 'tax_minor' => $tax, 'total_minor' => $subtotal + $tax]);

            return $line;
        });
    }
}
