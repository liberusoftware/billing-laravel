<?php

declare(strict_types=1);

namespace Liberu\Billing\Invoicing\Actions;

use Illuminate\Database\DatabaseManager;
use Liberu\Billing\Invoicing\Enums\InvoiceStatus;
use Liberu\Billing\Invoicing\Events\InvoiceDelivered;
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

        return $this->database->transaction(function () use ($invoice, $destination, $documentId): InvoiceSupport {
            $locked = Invoice::query()->lockForUpdate()->findOrFail($invoice->getKey());
            if (! in_array($locked->status, [InvoiceStatus::Finalized, InvoiceStatus::Paid], true)) {
                throw new \LogicException('Only finalized invoices can be delivered.');
            }

            $delivery = InvoiceSupport::query()->create(['team_id' => $locked->team_id, 'invoice_id' => $locked->getKey(), 'type' => 'delivery', 'status' => 'delivered', 'amount_minor' => 0, 'currency' => $locked->currency, 'destination' => strtolower(trim($destination)), 'payload' => ['document_id' => $documentId]]);
            InvoiceDelivered::dispatch($delivery);

            return $delivery;
        });
    }
}
