<?php

declare(strict_types=1);

namespace Liberu\Billing\Payments\Actions;

use Illuminate\Database\DatabaseManager;
use Illuminate\Support\Facades\Schema;
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

            if ($invoiceId !== null && Schema::hasTable('billing_invoices')) {
                $invoice = $this->database->table('billing_invoices')->where('id', $invoiceId)->first(['team_id', 'customer_id']);
                if ($invoice === null || ($invoice->team_id !== null && ($locked->team_id === null || (int) $invoice->team_id !== (int) $locked->team_id)) || ($invoice->customer_id !== null && ($locked->customer_id === null || (int) $invoice->customer_id !== (int) $locked->customer_id))) {
                    throw new \InvalidArgumentException('Payment invoice reference is invalid.');
                }
            } elseif ($invoiceId !== null) {
                throw new \InvalidArgumentException('Payment invoice reference is invalid.');
            }

            return PaymentAllocation::query()->create([
                'payment_id' => $locked->getKey(), 'invoice_id' => $invoiceId, 'amount_minor' => $amountMinor, 'currency' => $locked->currency,
            ]);
        });
    }
}
