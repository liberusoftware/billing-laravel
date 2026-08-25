<?php

declare(strict_types=1);

namespace Liberu\Billing\Payments\Actions;

use Illuminate\Database\DatabaseManager;
use Liberu\Billing\Payments\Enums\PaymentStatus;
use Liberu\Billing\Payments\Models\Payment;
use Liberu\Billing\Payments\Models\PaymentAllocation;

final readonly class AllocatePayment
{
    public function __construct(private DatabaseManager $database) {}

    public function execute(Payment $payment, int $amountMinor, ?int $invoiceId = null): PaymentAllocation
    {
        return $this->database->transaction(function () use ($payment, $amountMinor, $invoiceId): PaymentAllocation {
            $locked = Payment::query()->lockForUpdate()->findOrFail($payment->getKey());
            $allocated = (int) $locked->allocations()->sum('amount_minor');
            if ($locked->status !== PaymentStatus::Captured || $amountMinor < 1 || $allocated + $amountMinor > (int) $locked->amount_minor) {
                throw new \InvalidArgumentException('Allocation amount or payment state is invalid.');
            }

            return PaymentAllocation::query()->create([
                'payment_id' => $locked->getKey(), 'invoice_id' => $invoiceId, 'amount_minor' => $amountMinor, 'currency' => $locked->currency,
            ]);
        });
    }
}
