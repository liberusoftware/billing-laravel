<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Liberu\Billing\Payments\Actions\AllocatePayment;
use Liberu\Billing\Payments\Actions\CapturePayment;
use Liberu\Billing\Payments\Actions\CreatePayment;
use Liberu\Billing\Payments\Actions\OpenDispute;
use Liberu\Billing\Payments\Actions\ReconcilePayment;
use Liberu\Billing\Payments\Actions\RefundPayment;
use Liberu\Billing\Payments\Contracts\GatewayDriver;
use Liberu\Billing\Payments\Enums\DisputeStatus;
use Liberu\Billing\Payments\Enums\PaymentStatus;
use Liberu\Billing\Payments\Enums\ReconciliationStatus;
use Liberu\Billing\Payments\Models\Payment;
use Liberu\Billing\Payments\Services\GatewayManager;

uses(RefreshDatabase::class);

it('captures, allocates, refunds, disputes, and reconciles a payment', function () {
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
    $allocation = app(AllocatePayment::class)->execute($payment, 600, 15);
    $refund = app(RefundPayment::class)->execute($payment, 400);
    $dispute = app(OpenDispute::class)->execute($payment->refresh(), 100, 'customer claim');
    $reconciliation = app(ReconcilePayment::class)->execute($payment, 'capture-1');

    expect($payment->refresh()->status)->toBe(PaymentStatus::Disputed)
        ->and($allocation->amount_minor)->toBe(600)
        ->and($refund->amount_minor)->toBe(400)
        ->and($dispute->status)->toBe(DisputeStatus::Open)
        ->and($reconciliation->status)->toBe(ReconciliationStatus::Matched);
});

it('rejects over-allocation and refunds on a pending payment', function () {
    $payment = app(CreatePayment::class)->execute(['amount_minor' => 100, 'currency' => 'EUR']);

    expect(fn () => app(AllocatePayment::class)->execute($payment, 1))
        ->toThrow(InvalidArgumentException::class);
});
