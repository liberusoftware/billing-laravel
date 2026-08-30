<?php

declare(strict_types=1);

namespace Liberu\Billing\Invoicing\Actions;

use Illuminate\Database\DatabaseManager;
use Liberu\Billing\Invoicing\Enums\InvoiceStatus;
use Liberu\Billing\Invoicing\Events\InvoiceAdjusted;
use Liberu\Billing\Invoicing\Models\Invoice;
use Liberu\Billing\Invoicing\Models\InvoiceSupport;

final readonly class ApplyInvoiceAdjustment
{
    public function __construct(private DatabaseManager $database) {}

    public function execute(Invoice $invoice, int $amountMinor, string $reason, string $type = 'adjustment'): Invoice
    {
        if (! in_array($type, ['credit', 'adjustment'], true) || $amountMinor === 0 || trim($reason) === '') {
            throw new \InvalidArgumentException('Invoice adjustment details are invalid.');
        }

        return $this->database->transaction(function () use ($invoice, $amountMinor, $reason, $type): Invoice {
            $locked = Invoice::query()->lockForUpdate()->findOrFail($invoice->getKey());
            if ($locked->status === InvoiceStatus::Void || abs($amountMinor) > (int) $locked->total_minor && $amountMinor < 0) {
                throw new \LogicException('Invoice cannot accept this adjustment.');
            }
            $metadata = is_array($locked->metadata) ? $locked->metadata : [];
            $metadata['adjustments'][] = ['amount_minor' => $amountMinor, 'reason' => trim($reason), 'type' => $type, 'applied_at' => now()->toIso8601String()];
            $locked->update(['total_minor' => (int) $locked->total_minor + $amountMinor, 'metadata' => $metadata]);
            $adjustment = InvoiceSupport::query()->create(['team_id' => $locked->team_id, 'invoice_id' => $locked->getKey(), 'type' => $type, 'status' => 'applied', 'amount_minor' => abs($amountMinor), 'currency' => $locked->currency, 'payload' => ['amount_minor' => $amountMinor, 'reason' => trim($reason)]]);
            InvoiceAdjusted::dispatch($adjustment);

            return $locked->refresh();
        });
    }
}
