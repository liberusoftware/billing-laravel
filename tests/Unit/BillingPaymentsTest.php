<?php

use App\Models\Customer;
use App\Models\Team;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Liberu\Billing\Invoicing\Actions\AddInvoiceLine;
use Liberu\Billing\Invoicing\Actions\CreateInvoice;
use Liberu\Billing\Invoicing\Actions\FinalizeInvoice;
use Liberu\Billing\Payments\Actions\AllocatePayment;
use Liberu\Billing\Payments\Actions\CapturePayment;
use Liberu\Billing\Payments\Actions\CreatePayment;
use Liberu\Billing\Payments\Actions\CreatePaymentMandate;
use Liberu\Billing\Payments\Actions\CreatePaymentMethod;
use Liberu\Billing\Payments\Actions\OpenDispute;
use Liberu\Billing\Payments\Actions\ReconcilePayment;
use Liberu\Billing\Payments\Actions\RefundPayment;
use Liberu\Billing\Payments\Actions\TransitionPaymentMandate;
use Liberu\Billing\Payments\Actions\TransitionPaymentMethod;
use Liberu\Billing\Payments\Contracts\GatewayDriver;
use Liberu\Billing\Payments\Enums\DisputeStatus;
use Liberu\Billing\Payments\Enums\PaymentMethodStatus;
use Liberu\Billing\Payments\Enums\PaymentStatus;
use Liberu\Billing\Payments\Enums\ReconciliationStatus;
use Liberu\Billing\Payments\Events\PaymentAllocated;
use Liberu\Billing\Payments\Events\PaymentCaptured;
use Liberu\Billing\Payments\Events\PaymentDisputed;
use Liberu\Billing\Payments\Events\PaymentFailed;
use Liberu\Billing\Payments\Events\PaymentReconciled;
use Liberu\Billing\Payments\Events\PaymentRefunded;
use Liberu\Billing\Payments\Events\PaymentRefundFailed;
use Liberu\Billing\Payments\Models\Payment;
use Liberu\Billing\Payments\Models\PaymentRefund;
use Liberu\Billing\Payments\Services\GatewayManager;

uses(RefreshDatabase::class);

it('captures, allocates, refunds, disputes, and reconciles a payment', function () {
    Event::fake([
        PaymentAllocated::class,
        PaymentCaptured::class,
        PaymentDisputed::class,
        PaymentReconciled::class,
        PaymentRefunded::class,
    ]);

    app(GatewayManager::class)->register('test', new class() implements GatewayDriver
    {
        public function capture(Payment $payment): array
        {
            return ['reference' => 'capture-1'];
        }

        public function refund(Payment $payment, int $amountMinor): array
        {
            return ['reference' => 'refund-1'];
        }
    });

    $payment = app(CreatePayment::class)->execute(['team_id' => 10, 'amount_minor' => 1000, 'currency' => 'usd', 'gateway' => 'test']);
    $payment = app(CapturePayment::class)->execute($payment);
    $invoice = app(CreateInvoice::class)->execute(['team_id' => 10, 'currency' => 'USD']);
    app(AddInvoiceLine::class)->execute($invoice, 'Payment target', 1, 1000);
    $invoice = app(FinalizeInvoice::class)->execute($invoice->refresh());
    $allocation = app(AllocatePayment::class)->execute($payment, 600, $invoice->getKey());
    $refund = app(RefundPayment::class)->execute($payment, 400);
    $dispute = app(OpenDispute::class)->execute($payment->refresh(), 100, 'customer claim');
    $reconciliation = app(ReconcilePayment::class)->execute($payment, 'capture-1');
    $duplicateReconciliation = app(ReconcilePayment::class)->execute($payment, ' capture-1 ');

    expect($payment->refresh()->status)->toBe(PaymentStatus::Disputed)
        ->and($allocation->amount_minor)->toBe(600)
        ->and($refund->amount_minor)->toBe(400)
        ->and($dispute->status)->toBe(DisputeStatus::Open)
        ->and($reconciliation->status)->toBe(ReconciliationStatus::Matched)
        ->and($duplicateReconciliation->is($reconciliation))->toBeTrue();

    Event::assertDispatched(PaymentCaptured::class);
    Event::assertDispatched(PaymentAllocated::class);
    Event::assertDispatched(PaymentRefunded::class);
    Event::assertDispatched(PaymentDisputed::class);
    Event::assertDispatched(PaymentReconciled::class);
});

it('rejects over-allocation and refunds on a pending payment', function () {
    $payment = app(CreatePayment::class)->execute(['amount_minor' => 100, 'currency' => 'EUR']);

    expect(fn () => app(AllocatePayment::class)->execute($payment, 1))
        ->toThrow(InvalidArgumentException::class);
});

it('marks a payment failed when its gateway capture fails', function (): void {
    Event::fake([PaymentFailed::class]);
    app(GatewayManager::class)->register('failing', new class() implements GatewayDriver
    {
        public function capture(Payment $payment): array
        {
            throw new RuntimeException('Gateway unavailable.');
        }

        public function refund(Payment $payment, int $amountMinor): array
        {
            return ['reference' => 'unused'];
        }
    });

    $payment = app(CreatePayment::class)->execute(['amount_minor' => 100, 'currency' => 'USD', 'gateway' => 'failing']);

    expect(fn () => app(CapturePayment::class)->execute($payment))
        ->toThrow(RuntimeException::class, 'Gateway unavailable.');
    expect($payment->refresh()->status)->toBe(PaymentStatus::Failed);
    Event::assertDispatched(PaymentFailed::class);
});

it('transitions payment methods and prevents mandate reactivation after revocation', function (): void {
    $method = app(CreatePaymentMethod::class)->execute(['team_id' => 10, 'type' => 'card', 'provider' => 'stripe']);
    expect($method->status)->toBe(PaymentMethodStatus::Active);

    $method = app(TransitionPaymentMethod::class)->execute($method, PaymentMethodStatus::Inactive);
    expect($method->status)->toBe(PaymentMethodStatus::Inactive);

    $mandate = app(CreatePaymentMandate::class)->execute([
        'team_id' => 10,
        'payment_method_id' => $method->getKey(),
        'provider' => 'stripe',
    ]);
    $mandate = app(TransitionPaymentMandate::class)->execute($mandate, 'active');
    expect($mandate->status)->toBe('active');

    $mandate = app(TransitionPaymentMandate::class)->execute($mandate, 'revoked');
    expect($mandate->status)->toBe('revoked');
    expect(fn () => app(TransitionPaymentMandate::class)->execute($mandate, 'active'))
        ->toThrow(LogicException::class, 'cannot be reactivated');
});

it('rejects invalid dispute and reconciliation details', function () {
    $payment = app(CreatePayment::class)->execute(['amount_minor' => 100, 'currency' => 'EUR', 'gateway' => 'test']);

    expect(fn () => app(OpenDispute::class)->execute($payment, 1, ''))
        ->toThrow(InvalidArgumentException::class)
        ->and(fn () => app(ReconcilePayment::class)->execute($payment, ''))
        ->toThrow(InvalidArgumentException::class);
});

it('requires a gateway before refunding a captured payment', function () {
    $payment = app(CreatePayment::class)->execute(['amount_minor' => 100, 'currency' => 'EUR']);
    $payment->update(['status' => PaymentStatus::Captured]);

    expect(fn () => app(RefundPayment::class)->execute($payment->refresh(), 1))
        ->toThrow(InvalidArgumentException::class);
});

it('persists a failed refund attempt when its gateway fails', function (): void {
    Event::fake([PaymentRefundFailed::class]);
    app(GatewayManager::class)->register('refund-failing', new class() implements GatewayDriver
    {
        public function capture(Payment $payment): array
        {
            return ['reference' => 'capture-1'];
        }

        public function refund(Payment $payment, int $amountMinor): array
        {
            throw new RuntimeException('Refund provider unavailable.');
        }
    });

    $payment = app(CreatePayment::class)->execute(['amount_minor' => 100, 'currency' => 'USD', 'gateway' => 'refund-failing']);
    $payment = app(CapturePayment::class)->execute($payment);

    expect(fn () => app(RefundPayment::class)->execute($payment, 40))
        ->toThrow(RuntimeException::class, 'Refund provider unavailable.');
    expect(PaymentRefund::query()->where('payment_id', $payment->getKey())->firstOrFail()->status->value)->toBe('failed')
        ->and($payment->refresh()->refunded_minor)->toBe(0);
    Event::assertDispatched(PaymentRefundFailed::class);
});

it('rejects a payment method customer owned by another team', function (): void {
    $team = Team::factory()->create(['id' => 20]);
    $customerId = Customer::factory()->create(['team_id' => $team->getKey()])->getKey();

    expect(fn () => app(CreatePaymentMethod::class)->execute([
        'team_id' => 10, 'customer_id' => $customerId, 'type' => 'card', 'provider' => 'stripe',
    ]))->toThrow(InvalidArgumentException::class, 'Customer reference is invalid.');
});

it('rejects a payment customer owned by another team', function (): void {
    $team = Team::factory()->create(['id' => 20]);
    $customerId = Customer::factory()->create(['team_id' => $team->getKey()])->getKey();

    expect(fn () => app(CreatePayment::class)->execute([
        'team_id' => 10, 'customer_id' => $customerId, 'amount_minor' => 100, 'currency' => 'USD',
    ]))->toThrow(InvalidArgumentException::class, 'Customer reference is invalid.');
});

it('rejects an allocation invoice owned by another team', function (): void {
    $invoice = app(CreateInvoice::class)->execute(['team_id' => 20, 'currency' => 'USD']);
    $payment = app(CreatePayment::class)->execute(['team_id' => 10, 'amount_minor' => 100, 'currency' => 'USD']);
    $payment->update(['status' => PaymentStatus::Captured]);

    expect(fn () => app(AllocatePayment::class)->execute($payment->refresh(), 100, $invoice->getKey()))
        ->toThrow(InvalidArgumentException::class, 'Payment invoice reference is invalid.');
});

it('enforces invoice balances for partial allocations and marks the invoice paid', function (): void {
    Event::fake([PaymentAllocated::class]);
    $invoice = app(CreateInvoice::class)->execute(['team_id' => 10, 'currency' => 'USD']);
    app(AddInvoiceLine::class)->execute($invoice, 'Subscription', 1, 1000);
    $invoice = app(FinalizeInvoice::class)->execute($invoice->refresh());
    $payment = app(CreatePayment::class)->execute(['team_id' => 10, 'amount_minor' => 2000, 'currency' => 'USD']);
    $payment->update(['status' => PaymentStatus::Captured]);

    app(AllocatePayment::class)->execute($payment->refresh(), 400, $invoice->getKey());
    Event::assertDispatched(PaymentAllocated::class, fn (PaymentAllocated $event): bool => $event->allocation->amount_minor === 400);
    expect($invoice->refresh()->status->value)->toBe('partially_paid');
    expect(fn () => app(AllocatePayment::class)->execute($payment->refresh(), 601, $invoice->getKey()))
        ->toThrow(InvalidArgumentException::class, 'Allocation exceeds the invoice balance.');

    app(AllocatePayment::class)->execute($payment->refresh(), 600, $invoice->getKey());
    expect($invoice->refresh()->status->value)->toBe('paid');
});

it('rejects an allocation invoice for another customer', function (): void {
    Team::factory()->create(['id' => 10]);
    $paymentCustomer = Customer::factory()->create(['team_id' => 10]);
    $invoiceCustomer = Customer::factory()->create(['team_id' => 10]);
    $invoice = app(CreateInvoice::class)->execute(['team_id' => 10, 'customer_id' => $invoiceCustomer->getKey(), 'currency' => 'USD']);
    $payment = app(CreatePayment::class)->execute(['team_id' => 10, 'customer_id' => $paymentCustomer->getKey(), 'amount_minor' => 100, 'currency' => 'USD']);
    $payment->update(['status' => PaymentStatus::Captured]);

    expect(fn () => app(AllocatePayment::class)->execute($payment->refresh(), 100, $invoice->getKey()))
        ->toThrow(InvalidArgumentException::class, 'Payment invoice reference is invalid.');
});

it('does not open a dispute after the persisted payment state changes', function (): void {
    $payment = app(CreatePayment::class)->execute(['amount_minor' => 100, 'currency' => 'EUR']);
    $payment->update(['status' => PaymentStatus::Captured]);
    $payment->refresh();
    Payment::query()->whereKey($payment->getKey())->update(['status' => PaymentStatus::Disputed]);

    expect(fn () => app(OpenDispute::class)->execute($payment, 1, 'customer claim'))
        ->toThrow(InvalidArgumentException::class);
});
