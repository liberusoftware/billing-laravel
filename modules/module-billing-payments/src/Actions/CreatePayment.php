<?php

declare(strict_types=1);

namespace Liberu\Billing\Payments\Actions;

use Illuminate\Database\DatabaseManager;
use Liberu\Billing\Payments\Enums\PaymentStatus;
use Liberu\Billing\Payments\Models\Payment;

final readonly class CreatePayment
{
    public function __construct(private DatabaseManager $database) {}

    /** @param array<string,mixed> $attributes */
    public function execute(array $attributes): Payment
    {
        $amount = (int) ($attributes['amount_minor'] ?? 0);
        $currency = strtoupper((string) ($attributes['currency'] ?? ''));
        if ($amount < 1 || ! preg_match('/^[A-Z]{3}$/', $currency)) {
            throw new \InvalidArgumentException('Payment amount and currency are invalid.');
        }

        return $this->database->transaction(fn (): Payment => Payment::query()->create([
            'team_id' => $attributes['team_id'] ?? null,
            'customer_id' => $attributes['customer_id'] ?? null,
            'amount_minor' => $amount,
            'currency' => $currency,
            'status' => PaymentStatus::Pending,
            'payment_method' => $attributes['payment_method'] ?? null,
            'gateway' => $attributes['gateway'] ?? null,
            'refunded_minor' => 0,
            'metadata' => $attributes['metadata'] ?? [],
        ]));
    }
}
