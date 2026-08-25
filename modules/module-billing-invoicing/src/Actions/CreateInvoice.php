<?php

declare(strict_types=1);

namespace Liberu\Billing\Invoicing\Actions;

use Illuminate\Database\DatabaseManager;
use Liberu\Billing\Invoicing\Enums\InvoiceStatus;
use Liberu\Billing\Invoicing\Models\Invoice;

final readonly class CreateInvoice
{
    public function __construct(private DatabaseManager $database) {}

    public function execute(array $attributes): Invoice
    {
        $currency = strtoupper((string) ($attributes['currency'] ?? ''));
        if (! preg_match('/^[A-Z]{3}$/', $currency)) {
            throw new \InvalidArgumentException('Invoice currency is invalid.');
        }

        return $this->database->transaction(fn (): Invoice => Invoice::query()->create([
            'team_id' => $attributes['team_id'] ?? null, 'customer_id' => $attributes['customer_id'] ?? null,
            'number' => $attributes['number'] ?? null, 'status' => InvoiceStatus::Draft, 'currency' => $currency,
            'subtotal_minor' => 0, 'tax_minor' => 0, 'total_minor' => 0, 'due_at' => $attributes['due_at'] ?? null, 'metadata' => $attributes['metadata'] ?? [],
        ]));
    }
}
