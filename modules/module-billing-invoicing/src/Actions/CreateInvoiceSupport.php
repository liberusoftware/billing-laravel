<?php

declare(strict_types=1);

namespace Liberu\Billing\Invoicing\Actions;

use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Liberu\Billing\Invoicing\Models\InvoiceSupport;

final class CreateInvoiceSupport
{
    /** @param array<string,mixed> $attributes */
    public function execute(int $teamId, array $attributes): InvoiceSupport
    {
        $type = (string) ($attributes['type'] ?? '');
        if (! in_array($type, ['tax', 'credit', 'adjustment', 'pdf', 'delivery'], true) || (int) ($attributes['invoice_id'] ?? 0) < 1) {
            throw new InvalidArgumentException('Invoice support details are invalid.');
        }

return DB::transaction(fn (): InvoiceSupport => InvoiceSupport::query()->create(['team_id' => $teamId ?: null, 'invoice_id' => $attributes['invoice_id'], 'type' => $type, 'status' => $type === 'tax' || $type === 'adjustment' || $type === 'credit' ? 'applied' : 'pending', 'amount_minor' => max(0, (int) ($attributes['amount_minor'] ?? 0)), 'currency' => $attributes['currency'] ?? null, 'destination' => $attributes['destination'] ?? null, 'payload' => $attributes['payload'] ?? []]));
    }
}
