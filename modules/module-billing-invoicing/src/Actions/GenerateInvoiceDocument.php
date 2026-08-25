<?php

declare(strict_types=1);

namespace Liberu\Billing\Invoicing\Actions;

use Illuminate\Database\DatabaseManager;
use Dompdf\Dompdf;
use Liberu\Billing\Invoicing\Models\Invoice;
use Liberu\Billing\Invoicing\Models\InvoiceSupport;

final readonly class GenerateInvoiceDocument
{
    public function __construct(private DatabaseManager $database) {}

    public function execute(Invoice $invoice, string $format = 'pdf'): InvoiceSupport
    {
        if ($invoice->status->value === 'draft' || ! in_array(strtolower($format), ['pdf', 'html'], true)) {
            throw new \LogicException('Only finalized invoices can produce documents.');
        }
        $invoice->loadMissing('lines');
        $rows = $invoice->lines->map(fn ($line): string => '<li>'.e($line->description).' × '.e((string) $line->quantity).' — '.e((string) $line->amount_minor).'</li>')->implode('');
        $html = '<!doctype html><html><body><h1>Invoice '.e((string) ($invoice->number ?? $invoice->getKey())).'</h1><ul>'.$rows.'</ul><strong>Total: '.e((string) $invoice->total_minor).' '.e($invoice->currency).'</strong></body></html>';
        $format = strtolower($format);
        $payload = ['format' => $format, 'content' => $html, 'content_type' => $format === 'pdf' ? 'application/pdf' : 'text/html'];
        if ($format === 'pdf') {
            $renderer = new Dompdf();
            $renderer->loadHtml($html);
            $renderer->setPaper('a4');
            $renderer->render();
            $payload['content_base64'] = base64_encode($renderer->output());
        }

        return $this->database->transaction(fn (): InvoiceSupport => InvoiceSupport::query()->create(['team_id' => $invoice->team_id, 'invoice_id' => $invoice->getKey(), 'type' => $format, 'status' => 'generated', 'amount_minor' => 0, 'currency' => $invoice->currency, 'payload' => $payload]));
    }
}
