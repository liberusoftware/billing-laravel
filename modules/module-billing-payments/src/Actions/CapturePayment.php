<?php

declare(strict_types=1);

namespace Liberu\Billing\Payments\Actions;

use Illuminate\Database\DatabaseManager;
use Liberu\Billing\Payments\Enums\PaymentStatus;
use Liberu\Billing\Payments\Models\Payment;
use Liberu\Billing\Payments\Services\GatewayManager;

final readonly class CapturePayment
{
    public function __construct(private DatabaseManager $database, private GatewayManager $gateways) {}

    public function execute(Payment $payment): Payment
    {
        if ($payment->status === PaymentStatus::Captured) {
            return $payment;
        }
        if ($payment->status !== PaymentStatus::Pending || $payment->gateway === null) {
            throw new \LogicException('Payment is not capturable.');
        }

        $result = $this->gateways->driver($payment->gateway)->capture($payment);

        return $this->database->transaction(function () use ($payment, $result): Payment {
            $payment->update(['status' => PaymentStatus::Captured, 'captured_at' => now(), 'provider_reference' => $result['reference']]);

            return $payment->refresh();
        });
    }
}
