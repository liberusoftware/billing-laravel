<?php

declare(strict_types=1);

namespace Liberu\Billing\Payments\Actions;

use Illuminate\Database\DatabaseManager;
use Liberu\Billing\Payments\Models\PaymentMethod;

final readonly class CreatePaymentMethod
{
    public function __construct(private DatabaseManager $database) {}

    /** @param array<string,mixed> $attributes */
    public function execute(array $attributes): PaymentMethod
    {
        if (trim((string) ($attributes['type'] ?? '')) === '' || trim((string) ($attributes['provider'] ?? '')) === '') {
            throw new \InvalidArgumentException('Payment method type and provider are required.');
        }

        return $this->database->transaction(fn (): PaymentMethod => PaymentMethod::query()->create([
            'team_id' => $attributes['team_id'] ?? null,
            'customer_id' => $attributes['customer_id'] ?? null,
            'type' => $attributes['type'],
            'provider' => strtolower((string) $attributes['provider']),
            'provider_reference' => $attributes['provider_reference'] ?? null,
            'display_name' => $attributes['display_name'] ?? null,
            'last_four' => $attributes['last_four'] ?? null,
            'expires_at' => $attributes['expires_at'] ?? null,
            'is_default' => (bool) ($attributes['is_default'] ?? false),
            'metadata' => $attributes['metadata'] ?? [],
        ]));
    }
}
