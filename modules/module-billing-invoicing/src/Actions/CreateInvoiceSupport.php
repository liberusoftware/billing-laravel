<?php

declare(strict_types=1);

namespace Liberu\Billing\Invoicing\Actions;

use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Liberu\Billing\Invoicing\Events\InvoiceSupportCreated;
use Liberu\Billing\Invoicing\Models\Invoice;
use Liberu\Billing\Invoicing\Models\InvoiceSupport;

final class CreateInvoiceSupport
{
    /** @param array<string,mixed> $attributes */
    public function execute(int $teamId, array $attributes): InvoiceSupport
    {
        $type = (string) ($attributes['type'] ?? '');
        $invoiceId = (int) ($attributes['invoice_id'] ?? 0);
        $invoice = Invoice::query()->find($invoiceId);
        if (! in_array($type, ['tax', 'credit', 'adjustment', 'pdf', 'delivery'], true) || $invoice === null || ($invoice->team_id !== null && (int) $invoice->team_id !== $teamId)) {
            throw new InvalidArgumentException('Invoice support details are invalid.');
        }

        return DB::transaction(function () use ($teamId, $invoiceId, $type, $attributes): InvoiceSupport {
            $support = InvoiceSupport::query()->create(['team_id' => $teamId ?: null, 'invoice_id' => $invoiceId, 'type' => $type, 'status' => $type === 'tax' || $type === 'adjustment' || $type === 'credit' ? 'applied' : 'pending', 'amount_minor' => max(0, (int) ($attributes['amount_minor'] ?? 0)), 'currency' => $attributes['currency'] ?? null, 'destination' => $attributes['destination'] ?? null, 'payload' => $attributes['payload'] ?? []]);
            InvoiceSupportCreated::dispatch($support);

            return $support;
        });
    }
}
