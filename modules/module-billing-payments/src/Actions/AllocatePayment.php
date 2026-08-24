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
        $allocated = (int) $payment->allocations()->sum('amount_minor');
        if ($payment->status !== PaymentStatus::Captured || $amountMinor < 1 || $allocated + $amountMinor > (int) $payment->amount_minor) {
            throw new \InvalidArgumentException('Allocation amount or payment state is invalid.');
        }

        return $this->database->transaction(fn (): PaymentAllocation => PaymentAllocation::query()->create([
            'payment_id' => $payment->getKey(), 'invoice_id' => $invoiceId, 'amount_minor' => $amountMinor, 'currency' => $payment->currency,
        ]));
    }
}
