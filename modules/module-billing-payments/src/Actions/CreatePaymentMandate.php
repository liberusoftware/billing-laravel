<?php

declare(strict_types=1);

namespace Liberu\Billing\Payments\Actions;

use Illuminate\Database\DatabaseManager;
use Liberu\Billing\Payments\Models\PaymentMandate;
use Liberu\Billing\Payments\Models\PaymentMethod;
use Liberu\Billing\Payments\Support\CustomerReference;

final readonly class CreatePaymentMandate
{
    public function __construct(private DatabaseManager $database) {}

    /** @param array<string,mixed> $attributes */
    public function execute(array $attributes): PaymentMandate
    {
        if ((int) ($attributes['payment_method_id'] ?? 0) < 1 || trim((string) ($attributes['provider'] ?? '')) === '') {
            throw new \InvalidArgumentException('Payment method and provider are required.');
        }

        $teamId = $attributes['team_id'] ?? null;
        $method = PaymentMethod::query()->whereKey((int) $attributes['payment_method_id'])
            ->where(fn ($query) => $query->whereNull('team_id')->orWhere('team_id', $teamId))
            ->firstOrFail();
        $customerId = CustomerReference::assertBelongsToTeam($this->database, $attributes['customer_id'] ?? null, $teamId);
        if ($customerId !== null && $method->customer_id !== null && (int) $method->customer_id !== $customerId) {
            throw new \InvalidArgumentException('Payment method customer reference is invalid.');
        }

        return $this->database->transaction(fn (): PaymentMandate => PaymentMandate::query()->create([
            'team_id' => $teamId,
            'customer_id' => $customerId,
            'payment_method_id' => $attributes['payment_method_id'],
            'provider' => strtolower((string) $attributes['provider']),
            'provider_reference' => $attributes['provider_reference'] ?? null,
            'status' => $attributes['status'] ?? 'pending',
            'metadata' => $attributes['metadata'] ?? [],
        ]));
    }
}
