<?php

declare(strict_types=1);

namespace Liberu\Billing\Invoicing\Actions;

use Carbon\CarbonInterface;
use Illuminate\Database\DatabaseManager;
use Liberu\Billing\Invoicing\Enums\InvoiceStatus;
use Liberu\Billing\Invoicing\Models\Invoice;
use Liberu\Billing\Invoicing\Models\InvoiceSupport;

final readonly class ApplyInvoiceLateFee
{
    public function __construct(private DatabaseManager $database) {}

    public function execute(Invoice $invoice, int $amountMinor, ?CarbonInterface $at = null): Invoice
    {
        if ($amountMinor < 1) {
            throw new \InvalidArgumentException('Invoice late fee must be positive.');
        }

        $at ??= now();

        return $this->database->transaction(function () use ($invoice, $amountMinor, $at): Invoice {
            $locked = Invoice::query()->lockForUpdate()->findOrFail($invoice->getKey());
            if ($locked->status !== InvoiceStatus::Finalized || $locked->due_at === null || $locked->due_at->isAfter($at)) {
                throw new \LogicException('Only overdue finalized invoices can receive a late fee.');
            }

            $metadata = is_array($locked->metadata) ? $locked->metadata : [];
            $feeDate = $at->toDateString();
            $lateFees = is_array($metadata['late_fees'] ?? null) ? $metadata['late_fees'] : [];
            foreach ($lateFees as $lateFee) {
                if (is_array($lateFee) && ($lateFee['date'] ?? null) === $feeDate) {
                    return $locked->refresh();
                }
            }

            $lateFees[] = ['date' => $feeDate, 'amount_minor' => $amountMinor, 'applied_at' => $at->toIso8601String()];
            $metadata['late_fees'] = $lateFees;
            $locked->update(['total_minor' => (int) $locked->total_minor + $amountMinor, 'metadata' => $metadata]);
            InvoiceSupport::query()->create([
                'team_id' => $locked->team_id,
                'invoice_id' => $locked->getKey(),
                'type' => 'adjustment',
                'status' => 'applied',
                'amount_minor' => $amountMinor,
                'currency' => $locked->currency,
                'payload' => ['amount_minor' => $amountMinor, 'reason' => 'late_fee', 'date' => $feeDate],
            ]);

            return $locked->refresh();
        });
    }
}
