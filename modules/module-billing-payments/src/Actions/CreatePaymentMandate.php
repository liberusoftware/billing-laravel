<?php

declare(strict_types=1);

namespace Liberu\Billing\Payments\Actions;

use Illuminate\Database\DatabaseManager;
use Liberu\Billing\Payments\Models\PaymentMandate;

final readonly class CreatePaymentMandate
{
    public function __construct(private DatabaseManager $database) {}

    /** @param array<string,mixed> $attributes */
    public function execute(array $attributes): PaymentMandate
    {
        if ((int) ($attributes['payment_method_id'] ?? 0) < 1 || trim((string) ($attributes['provider'] ?? '')) === '') {
            throw new \InvalidArgumentException('Payment method and provider are required.');
        }

        return $this->database->transaction(fn (): PaymentMandate => PaymentMandate::query()->create([
            'team_id' => $attributes['team_id'] ?? null,
            'customer_id' => $attributes['customer_id'] ?? null,
            'payment_method_id' => $attributes['payment_method_id'],
            'provider' => strtolower((string) $attributes['provider']),
            'provider_reference' => $attributes['provider_reference'] ?? null,
            'status' => $attributes['status'] ?? 'pending',
            'metadata' => $attributes['metadata'] ?? [],
        ]));
    }
}
