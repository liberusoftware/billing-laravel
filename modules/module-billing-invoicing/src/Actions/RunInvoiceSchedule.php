<?php

declare(strict_types=1);

namespace Liberu\Billing\Invoicing\Actions;

use Carbon\CarbonInterface;
use Illuminate\Database\DatabaseManager;
use Liberu\Billing\Invoicing\Enums\InvoiceStatus;
use Liberu\Billing\Invoicing\Events\InvoiceScheduleRun;
use Liberu\Billing\Invoicing\Models\Invoice;
use Liberu\Billing\Invoicing\Models\InvoiceSchedule;

final readonly class RunInvoiceSchedule
{
    public function __construct(
        private DatabaseManager $database,
        private CreateInvoice $createInvoice,
        private AddInvoiceLine $addInvoiceLine,
        private FinalizeInvoice $finalizeInvoice,
    ) {}

    public function execute(InvoiceSchedule $schedule, ?CarbonInterface $at = null): Invoice
    {
        $at ??= now();

        return $this->database->transaction(function () use ($schedule, $at): Invoice {
            // Claim the due schedule before creating the invoice. Keeping this
            // lock for the whole transaction prevents concurrent workers from
            // generating multiple invoices for the same billing period.
            $schedule = InvoiceSchedule::query()->lockForUpdate()->findOrFail($schedule->getKey());
            if (! $schedule->active) {
                throw new \LogicException('Inactive invoice schedules cannot be run.');
            }
            if ($schedule->next_run_at !== null && $schedule->next_run_at->isFuture()) {
                throw new \LogicException('Invoice schedule is not due yet.');
            }

            $metadata = is_array($schedule->metadata) ? $schedule->metadata : [];
            $invoice = $this->createInvoice->execute([
                'team_id' => $schedule->team_id,
                'customer_id' => $schedule->customer_id,
                'currency' => $metadata['currency'] ?? 'USD',
                'due_at' => $metadata['due_at'] ?? null,
                'metadata' => ['invoice_schedule_id' => $schedule->getKey()],
            ]);

            foreach (($metadata['lines'] ?? []) as $line) {
                if (! is_array($line)) {
                    throw new \InvalidArgumentException('Invoice schedule lines must be arrays.');
                }
                $this->addInvoiceLine->execute(
                    $invoice->refresh(),
                    (string) ($line['description'] ?? ''),
                    (int) ($line['quantity'] ?? 0),
                    (int) ($line['unit_amount_minor'] ?? -1),
                    (float) ($line['tax_rate'] ?? 0),
                );
            }

            if ($invoice->refresh()->status === InvoiceStatus::Draft && $invoice->lines()->exists()) {
                $invoice = $this->finalizeInvoice->execute($invoice->refresh());
            }

            $nextRun = $schedule->next_run_at?->copy() ?? $at->copy();
            $nextRun = match ($schedule->frequency) {
                'daily' => $nextRun->addDay(),
                'weekly' => $nextRun->addWeek(),
                'monthly' => $nextRun->addMonth(),
                'yearly' => $nextRun->addYear(),
                default => throw new \LogicException('Invoice schedule frequency is invalid.'),
            };
            $schedule->update(['next_run_at' => $nextRun]);
            InvoiceScheduleRun::dispatch($invoice->refresh(), (int) $schedule->getKey());

            return $invoice->refresh();
        });
    }
}
