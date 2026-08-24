<?php

declare(strict_types=1);

namespace Liberu\Billing\Usage\Actions;

use Illuminate\Database\DatabaseManager;
use Illuminate\Database\QueryException;
use Liberu\Billing\Usage\Models\Meter;
use Liberu\Billing\Usage\Models\UsageRecord;

final readonly class IngestUsage
{
    public function __construct(private DatabaseManager $database) {}

    public function execute(Meter $meter, array $attributes): UsageRecord
    {
        $key = (string) ($attributes['event_key'] ?? '');
        $quantity = (float) ($attributes['quantity'] ?? 0);
        if (! $meter->active || $key === '' || $quantity <= 0) {
            throw new \InvalidArgumentException('Usage event or quantity is invalid.');
        }
        $existing = UsageRecord::query()->where('meter_id', $meter->getKey())->where('event_key', $key)->first();
        if ($existing) {
            return $existing;
        }

        try {
            return $this->database->transaction(fn (): UsageRecord => UsageRecord::query()->create(['team_id' => $meter->team_id, 'customer_id' => $attributes['customer_id'] ?? null, 'meter_id' => $meter->getKey(), 'event_key' => $key, 'quantity' => $quantity, 'unit_price_minor' => $meter->unit_price_minor, 'amount_minor' => (int) round($quantity * (int) $meter->unit_price_minor), 'currency' => $meter->currency, 'occurred_at' => $attributes['occurred_at'] ?? now(), 'metadata' => $attributes['metadata'] ?? []]));
        } catch (QueryException $exception) {
            if (! str_contains(strtolower($exception->getMessage()), 'unique')) {
                throw $exception;
            }

            return UsageRecord::query()->where('meter_id', $meter->getKey())->where('event_key', $key)->firstOrFail();
        }
    }
}
