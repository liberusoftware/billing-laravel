<?php

use App\Mail\DisputeCreated;
use App\Mail\DisputeMessageReceived;
use App\Mail\DisputeStatusUpdated;
use App\Mail\OverdueInvoiceReminder;
use App\Mail\PaymentConfirmation;
use App\Models\Invoice;
use App\Models\InvoiceDispute;
use App\Models\Payment;

beforeEach(function (): void {
    $this->invoice = (new Invoice())->forceFill([
        'invoice_number' => 'INV-VIEW-1',
        'total_amount' => 125,
        'currency' => 'USD',
        'due_date' => now()->subDay(),
    ]);
});

it('renders dispute notifications with escaped customer content', function (string $class): void {
    $dispute = (new InvoiceDispute())->forceFill([
        'reason' => 'Incorrect amount',
        'description' => '<script>alert("bad")</script>',
        'resolution_notes' => '<script>alert("bad")</script>',
        'status' => 'resolved',
    ])->setRelation('invoice', $this->invoice);

    expect((new $class($dispute))->render())
        ->toContain('INV-VIEW-1')
        ->not->toContain('<script>alert("bad")</script>');
})->with([DisputeCreated::class, DisputeMessageReceived::class, DisputeStatusUpdated::class]);

it('renders overdue invoice reminders', function (): void {
    expect((new OverdueInvoiceReminder($this->invoice))->render())
        ->toContain('INV-VIEW-1', '125.00', 'USD');
});

it('renders payment confirmations', function (): void {
    $payment = (new Payment())->forceFill(['amount' => 125, 'currency' => 'USD'])
        ->setRelation('invoice', $this->invoice);

    expect((new PaymentConfirmation($payment))->render())
        ->toContain('INV-VIEW-1', '125.00', 'USD');
});
