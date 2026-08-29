<?php

declare(strict_types=1);

namespace Liberu\Billing\Usage\Actions;

use Illuminate\Database\DatabaseManager;
use Liberu\Billing\Usage\Models\UsageRecord;

final readonly class CorrectUsage
{
    public function __construct(private DatabaseManager $database) {}

    public function execute(UsageRecord $record, float $quantity, string $eventKey): UsageRecord
    {
        $eventKey = trim($eventKey);
        if ($quantity == 0.0 || ! is_finite($quantity) || $eventKey === '') {
            throw new \InvalidArgumentException('Correction quantity and event key are invalid.');
        }

        return $this->database->transaction(function () use ($record, $quantity, $eventKey): UsageRecord {
            $source = UsageRecord::query()->lockForUpdate()->findOrFail($record->getKey());
            $existing = UsageRecord::query()->where('meter_id', $source->meter_id)->where('event_key', $eventKey)->first();
            if ($existing !== null) {
                return $existing;
            }

            return UsageRecord::query()->create(['team_id' => $source->team_id, 'customer_id' => $source->customer_id, 'meter_id' => $source->meter_id, 'event_key' => $eventKey, 'quantity' => $quantity, 'unit_price_minor' => $source->unit_price_minor, 'amount_minor' => (int) round($quantity * (int) $source->unit_price_minor), 'currency' => $source->currency, 'occurred_at' => now(), 'corrects_id' => $source->getKey(), 'metadata' => ['correction' => true]]);
        });
    }
}
