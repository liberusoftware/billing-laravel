<?php

declare(strict_types=1);

namespace Liberu\Billing\Catalog\Actions;

use Illuminate\Database\DatabaseManager;
use Liberu\Billing\Catalog\Enums\CatalogStatus;
use Liberu\Billing\Catalog\Events\CatalogRecordCreated;
use Liberu\Billing\Catalog\Models\CatalogRecord;

final readonly class CreateCatalogRecord
{
    public function __construct(private DatabaseManager $database) {}

    /** @param array<string, mixed> $attributes */
    public function execute(string $model, array $attributes): CatalogRecord
    {
        return $this->database->transaction(function () use ($model, $attributes): CatalogRecord {
            $record = $model::query()->create([
            'team_id' => $attributes['team_id'] ?? null,
            'name' => trim((string) $attributes['name']),
            'code' => strtoupper(trim((string) $attributes['code'])),
            'description' => $attributes['description'] ?? null,
            'status' => CatalogStatus::Draft,
            'configuration' => $attributes['configuration'] ?? [],
            ]);
            CatalogRecordCreated::dispatch($record);

            return $record;
        });
    }
}
