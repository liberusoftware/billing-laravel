<?php

declare(strict_types=1);

namespace Liberu\Billing\Payments\Actions;

use Illuminate\Database\DatabaseManager;
use Liberu\Billing\Payments\Enums\PaymentStatus;
use Liberu\Billing\Payments\Enums\RefundStatus;
use Liberu\Billing\Payments\Models\Payment;
use Liberu\Billing\Payments\Models\PaymentRefund;
use Liberu\Billing\Payments\Services\GatewayManager;

final readonly class RefundPayment
{
    public function __construct(private DatabaseManager $database, private GatewayManager $gateways) {}

    public function execute(Payment $payment, int $amountMinor, string $reason = 'requested'): PaymentRefund
    {
        $refunded = (int) $payment->refunded_minor;
        if ($payment->status !== PaymentStatus::Captured || trim((string) $payment->gateway) === '' || $amountMinor < 1 || $refunded + $amountMinor > (int) $payment->amount_minor) {
            throw new \InvalidArgumentException('Refund amount or payment state is invalid.');
        }
        $result = $this->gateways->driver((string) $payment->gateway)->refund($payment, $amountMinor);

        return $this->database->transaction(function () use ($payment, $amountMinor, $reason, $result): PaymentRefund {
            $locked = Payment::query()->lockForUpdate()->findOrFail($payment->getKey());
            $total = (int) $locked->refunded_minor + $amountMinor;
            if ($locked->status !== PaymentStatus::Captured || $total > (int) $locked->amount_minor) {
                throw new \InvalidArgumentException('Refund amount or payment state is invalid.');
            }
            $refund = PaymentRefund::query()->create(['payment_id' => $locked->getKey(), 'amount_minor' => $amountMinor, 'status' => RefundStatus::Completed, 'provider_reference' => $result['reference'], 'reason' => $reason]);
            $locked->update(['refunded_minor' => $total, 'status' => $total === (int) $locked->amount_minor ? PaymentStatus::Refunded : PaymentStatus::Captured]);

            return $refund;
        });
    }
}
