<?php

declare(strict_types=1);

namespace Liberu\Billing\Payments\Actions;

use Illuminate\Database\DatabaseManager;
use Liberu\Billing\Payments\Enums\DisputeStatus;
use Liberu\Billing\Payments\Enums\PaymentStatus;
use Liberu\Billing\Payments\Models\Payment;
use Liberu\Billing\Payments\Models\PaymentDispute;

final readonly class OpenDispute
{
    public function __construct(private DatabaseManager $database) {}

    public function execute(Payment $payment, int $amountMinor, string $reason): PaymentDispute
    {
        if ($payment->status !== PaymentStatus::Captured || trim($reason) === '' || $amountMinor < 1 || $amountMinor > (int) $payment->amount_minor) {
            throw new \InvalidArgumentException('Dispute amount or payment state is invalid.');
        }

        return $this->database->transaction(function () use ($payment, $amountMinor, $reason): PaymentDispute {
            $locked = Payment::query()->lockForUpdate()->findOrFail($payment->getKey());
            if ($locked->status !== PaymentStatus::Captured || $amountMinor > (int) $locked->amount_minor) {
                throw new \InvalidArgumentException('Dispute amount or payment state is invalid.');
            }

            $locked->update(['status' => PaymentStatus::Disputed]);

            return PaymentDispute::query()->create(['payment_id' => $locked->getKey(), 'amount_minor' => $amountMinor, 'status' => DisputeStatus::Open, 'reason' => trim($reason)]);
        });
    }
}
