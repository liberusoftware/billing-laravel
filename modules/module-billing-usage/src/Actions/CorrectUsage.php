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
        if ($quantity == 0.0 || $eventKey === '') {
            throw new \InvalidArgumentException('Correction quantity and event key are invalid.');
        }

        return $this->database->transaction(fn (): UsageRecord => UsageRecord::query()->create(['team_id' => $record->team_id, 'customer_id' => $record->customer_id, 'meter_id' => $record->meter_id, 'event_key' => $eventKey, 'quantity' => $quantity, 'unit_price_minor' => $record->unit_price_minor, 'amount_minor' => (int) round($quantity * (int) $record->unit_price_minor), 'currency' => $record->currency, 'occurred_at' => now(), 'corrects_id' => $record->getKey(), 'metadata' => ['correction' => true]]));
    }
}
