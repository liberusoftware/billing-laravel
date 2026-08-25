<?php

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Liberu\Billing\Invoicing\Actions\AddInvoiceLine;
use Liberu\Billing\Invoicing\Actions\ApplyInvoiceAdjustment;
use Liberu\Billing\Invoicing\Actions\CreateInvoice;
use Liberu\Billing\Invoicing\Actions\CreateInvoiceSchedule;
use Liberu\Billing\Invoicing\Actions\CreateInvoiceSupport;
use Liberu\Billing\Invoicing\Actions\DeliverInvoice;
use Liberu\Billing\Invoicing\Actions\FinalizeInvoice;
use Liberu\Billing\Invoicing\Actions\GenerateInvoiceDocument;
use Liberu\Billing\Invoicing\Actions\RunInvoiceSchedule;
use Liberu\Billing\Invoicing\Enums\InvoiceStatus;
use Liberu\Billing\Invoicing\Policies\InvoicePolicy;

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

it('recalculates tax across all invoice lines', function () {
    $invoice = app(CreateInvoice::class)->execute(['team_id' => 10, 'currency' => 'USD']);

    app(AddInvoiceLine::class)->execute($invoice, 'Standard', 1, 1000, 20);
    app(AddInvoiceLine::class)->execute($invoice->refresh(), 'Reduced', 1, 1000, 5);

    expect($invoice->refresh()->tax_minor)->toBe(250)
        ->and($invoice->total_minor)->toBe(2250);
});

it('rejects invoice support for another team', function () {
    $invoice = app(CreateInvoice::class)->execute(['team_id' => 20, 'currency' => 'USD']);

    expect(fn () => app(CreateInvoiceSupport::class)->execute(10, ['invoice_id' => $invoice->id, 'type' => 'pdf']))
        ->toThrow(InvalidArgumentException::class);
});

it('rejects empty finalization and invalid currency', function () {
    expect(fn () => app(CreateInvoice::class)->execute(['currency' => 'EURO']))
        ->toThrow(InvalidArgumentException::class);

    $invoice = app(CreateInvoice::class)->execute(['currency' => 'USD']);
    expect(fn () => app(FinalizeInvoice::class)->execute($invoice))
        ->toThrow(LogicException::class);
});

it('allows read tokens to view but not update their team invoice', function () {
    $invoice = app(CreateInvoice::class)->execute(['team_id' => 10, 'currency' => 'USD']);
    $readUser = new class() implements Authenticatable
    {
        public int $current_team_id = 10;

        public function tokenCan(string $ability): bool
        {
            return $ability === 'billing.invoicing.read';
        }

        public function can(string $ability): bool
        {
            return false;
        }

        public function getAuthIdentifierName(): string
        {
            return 'id';
        }

        public function getAuthIdentifier(): int
        {
            return 1;
        }

        public function getAuthPasswordName(): string
        {
            return 'password';
        }

        public function getAuthPassword(): ?string
        {
            return null;
        }

        public function getRememberToken(): string
        {
            return '';
        }

        public function setRememberToken($value): void {}

        public function getRememberTokenName(): string
        {
            return 'remember_token';
        }
    };

    $policy = app(InvoicePolicy::class);
    expect($policy->view($readUser, $invoice))->toBeTrue()
        ->and($policy->update($readUser, $invoice))->toBeFalse();
});

it('runs a due recurring schedule and advances its next run', function () {
    $schedule = app(CreateInvoiceSchedule::class)->execute([
        'team_id' => 10,
        'frequency' => 'monthly',
        'next_run_at' => now()->subMinute(),
        'metadata' => [
            'currency' => 'eur',
            'lines' => [['description' => 'Hosting', 'quantity' => 1, 'unit_amount_minor' => 2500, 'tax_rate' => 20]],
        ],
    ]);

    $invoice = app(RunInvoiceSchedule::class)->execute($schedule);

    expect($invoice->status)->toBe(InvoiceStatus::Finalized)
        ->and($invoice->total_minor)->toBe(3000)
        ->and($schedule->refresh()->next_run_at->isFuture())->toBeTrue();
});

it('rejects invalid invoice schedule frequencies', function () {
    expect(fn () => app(CreateInvoiceSchedule::class)->execute(['frequency' => 'hourly']))
        ->toThrow(InvalidArgumentException::class);
});

it('applies credits and generates and delivers an invoice document', function () {
    $invoice = app(CreateInvoice::class)->execute(['team_id' => 10, 'currency' => 'USD']);
    app(AddInvoiceLine::class)->execute($invoice, 'Service', 1, 1000, 0);
    $invoice = app(FinalizeInvoice::class)->execute($invoice->refresh());

    $invoice = app(ApplyInvoiceAdjustment::class)->execute($invoice, -250, 'Service credit', 'credit');
    $document = app(GenerateInvoiceDocument::class)->execute($invoice);
    $delivery = app(DeliverInvoice::class)->execute($invoice, 'billing@example.com', $document->id);

    expect($invoice->total_minor)->toBe(750)
        ->and($document->type)->toBe('pdf')
        ->and($document->status)->toBe('generated')
        ->and($document->payload['content'])->toContain('Service')
        ->and(base64_decode($document->payload['content_base64'], true))->toStartWith('%PDF')
        ->and($delivery->status)->toBe('delivered')
        ->and($delivery->destination)->toBe('billing@example.com');
});
