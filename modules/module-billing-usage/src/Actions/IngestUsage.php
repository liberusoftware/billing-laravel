<?php

declare(strict_types=1);

namespace Liberu\Billing\Usage\Actions;

use Illuminate\Database\DatabaseManager;
use Illuminate\Database\QueryException;
use Liberu\Billing\Usage\Events\UsageIngested;
use Liberu\Billing\Usage\Models\Meter;
use Liberu\Billing\Usage\Models\UsageRecord;
use Liberu\Billing\Usage\Support\CustomerReference;

final readonly class IngestUsage
{
    public function __construct(private DatabaseManager $database) {}

    public function execute(Meter $meter, array $attributes): UsageRecord
    {
        $key = trim((string) ($attributes['event_key'] ?? ''));
        $quantity = (float) ($attributes['quantity'] ?? 0);
        if ($key === '' || $quantity <= 0 || ! is_finite($quantity)) {
            throw new \InvalidArgumentException('Usage event or quantity is invalid.');
        }

        try {
            return $this->database->transaction(function () use ($meter, $attributes, $key, $quantity): UsageRecord {
                $locked = Meter::query()->lockForUpdate()->findOrFail($meter->getKey());
                if (! $locked->active) {
                    throw new \LogicException('Usage cannot be ingested into an inactive meter.');
                }

                $customerId = CustomerReference::assertBelongsToTeam($this->database, $attributes['customer_id'] ?? null, $locked->team_id);

                $existing = UsageRecord::query()->where('meter_id', $locked->getKey())->where('event_key', $key)->first();
                if ($existing !== null) {
                    return $existing;
                }

                $record = UsageRecord::query()->create(['team_id' => $locked->team_id, 'customer_id' => $customerId, 'meter_id' => $locked->getKey(), 'event_key' => $key, 'quantity' => $quantity, 'unit_price_minor' => $locked->unit_price_minor, 'amount_minor' => (int) round($quantity * (int) $locked->unit_price_minor), 'currency' => $locked->currency, 'occurred_at' => $attributes['occurred_at'] ?? now(), 'metadata' => $attributes['metadata'] ?? []]);
                UsageIngested::dispatch($record);

                return $record;
            });
        } catch (QueryException $exception) {
            if (! str_contains(strtolower($exception->getMessage()), 'unique')) {
                throw $exception;
            }

            return UsageRecord::query()->where('meter_id', $meter->getKey())->where('event_key', $key)->firstOrFail();
        }
    }
}
