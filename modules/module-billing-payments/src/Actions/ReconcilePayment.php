<?php

declare(strict_types=1);

namespace Liberu\Billing\Payments\Actions;

use Illuminate\Database\DatabaseManager;
use Liberu\Billing\Payments\Enums\ReconciliationStatus;
use Liberu\Billing\Payments\Models\Payment;
use Liberu\Billing\Payments\Models\PaymentReconciliation;

final readonly class ReconcilePayment
{
    public function __construct(private DatabaseManager $database) {}

    public function execute(Payment $payment, string $providerReference, bool $matched = true, ?string $notes = null): PaymentReconciliation
    {
        if (trim($providerReference) === '') {
            throw new \InvalidArgumentException('A provider reference is required.');
        }

        $providerReference = trim($providerReference);

        return $this->database->transaction(function () use ($payment, $providerReference, $matched, $notes): PaymentReconciliation {
            $locked = Payment::query()->lockForUpdate()->findOrFail($payment->getKey());
            $existing = PaymentReconciliation::query()
                ->where('payment_id', $locked->getKey())
                ->where('provider_reference', $providerReference)
                ->first();

            if ($existing !== null) {
                return $existing;
            }

            return PaymentReconciliation::query()->create([
                'payment_id' => $locked->getKey(), 'status' => $matched ? ReconciliationStatus::Matched : ReconciliationStatus::Mismatch,
                'provider_reference' => $providerReference, 'notes' => $notes,
            ]);
        });
    }
}
