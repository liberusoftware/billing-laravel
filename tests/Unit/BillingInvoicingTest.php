<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Liberu\Billing\Invoicing\Actions\AddInvoiceLine;
use Liberu\Billing\Invoicing\Actions\CreateInvoice;
use Liberu\Billing\Invoicing\Actions\FinalizeInvoice;
use Liberu\Billing\Invoicing\Enums\InvoiceStatus;

uses(RefreshDatabase::class);

it('generates and finalizes an invoice with tax', function () {
    $invoice = app(CreateInvoice::class)->execute(['team_id' => 10, 'currency' => 'eur']);
    app(AddInvoiceLine::class)->execute($invoice, 'Service', 2, 500, 20);
    $invoice = app(FinalizeInvoice::class)->execute($invoice->refresh());

    expect($invoice->status)->toBe(InvoiceStatus::Finalized)
        ->and($invoice->subtotal_minor)->toBe(1000)
        ->and($invoice->tax_minor)->toBe(200)
        ->and($invoice->total_minor)->toBe(1200);
});

it('rejects empty finalization and invalid currency', function () {
    expect(fn () => app(CreateInvoice::class)->execute(['currency' => 'EURO']))
        ->toThrow(InvalidArgumentException::class);

    $invoice = app(CreateInvoice::class)->execute(['currency' => 'USD']);
    expect(fn () => app(FinalizeInvoice::class)->execute($invoice))
        ->toThrow(LogicException::class);
});
