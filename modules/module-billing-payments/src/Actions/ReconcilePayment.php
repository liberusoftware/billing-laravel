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

        return $this->database->transaction(fn (): PaymentReconciliation => PaymentReconciliation::query()->create([
            'payment_id' => $payment->getKey(), 'status' => $matched ? ReconciliationStatus::Matched : ReconciliationStatus::Mismatch,
            'provider_reference' => trim($providerReference), 'notes' => $notes,
        ]));
    }
}
