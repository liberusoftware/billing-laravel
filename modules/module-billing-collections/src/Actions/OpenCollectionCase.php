<?php

declare(strict_types=1);

namespace Liberu\Billing\Collections\Actions;

use Illuminate\Database\DatabaseManager;
use Liberu\Billing\Collections\Enums\CollectionStatus;
use Liberu\Billing\Collections\Models\CollectionCase;

final readonly class OpenCollectionCase
{
    public function __construct(private DatabaseManager $database) {}

    public function execute(array $attributes): CollectionCase
    {
        $amount = (int) ($attributes['amount_minor'] ?? 0);
        $currency = strtoupper((string) ($attributes['currency'] ?? ''));
        $type = strtolower(trim((string) ($attributes['type'] ?? 'dunning')));
        if ($amount < 1 || ! preg_match('/^[A-Z]{3}$/', $currency)) {
            throw new \InvalidArgumentException('Collection amount and currency are invalid.');
        }
        if (! in_array($type, ['retry', 'dunning', 'reminder', 'promise', 'credit_control', 'suspension', 'write_off', 'recovery'], true)) {
            throw new \InvalidArgumentException('Collection case type is invalid.');
        }

        return $this->database->transaction(fn (): CollectionCase => CollectionCase::query()->create([
            'team_id' => $attributes['team_id'] ?? null, 'customer_id' => $attributes['customer_id'] ?? null, 'invoice_id' => $attributes['invoice_id'] ?? null,
            'type' => $type, 'status' => CollectionStatus::Open, 'amount_minor' => $amount, 'currency' => $currency,
            'next_action_at' => $attributes['next_action_at'] ?? now(), 'reason' => $attributes['reason'] ?? null, 'metadata' => $attributes['metadata'] ?? [],
        ]));
    }
}
