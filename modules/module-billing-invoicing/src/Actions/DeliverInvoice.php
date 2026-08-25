<?php

declare(strict_types=1);

namespace Liberu\Billing\Invoicing\Actions;

use Illuminate\Database\DatabaseManager;
use Liberu\Billing\Invoicing\Models\Invoice;
use Liberu\Billing\Invoicing\Models\InvoiceSupport;

final readonly class DeliverInvoice
{
    public function __construct(private DatabaseManager $database) {}

    public function execute(Invoice $invoice, string $destination, ?int $documentId = null): InvoiceSupport
    {
        if (trim($destination) === '' || ! filter_var($destination, FILTER_VALIDATE_EMAIL)) {
            throw new \InvalidArgumentException('A valid invoice delivery destination is required.');
        }

        return $this->database->transaction(fn (): InvoiceSupport => InvoiceSupport::query()->create(['team_id' => $invoice->team_id, 'invoice_id' => $invoice->getKey(), 'type' => 'delivery', 'status' => 'delivered', 'amount_minor' => 0, 'currency' => $invoice->currency, 'destination' => strtolower(trim($destination)), 'payload' => ['document_id' => $documentId]]));
    }
}
