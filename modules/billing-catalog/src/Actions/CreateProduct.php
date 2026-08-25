<?php

declare(strict_types=1);

namespace Liberu\Billing\Catalog\Actions;

use Illuminate\Database\DatabaseManager;
use Liberu\Billing\Catalog\Enums\ProductStatus;
use Liberu\Billing\Catalog\Models\Product;

final readonly class CreateProduct
{
    public function __construct(private DatabaseManager $database) {}

    /** @param array{team_id?: int|null, name: string, sku: string, description?: string|null, base_price_minor: int, currency: string, metadata?: array<string, mixed>} $attributes */
    public function execute(array $attributes): Product
    {
        return $this->database->transaction(fn (): Product => Product::query()->create([
            'team_id' => $attributes['team_id'] ?? null,
            'name' => trim($attributes['name']),
            'sku' => strtoupper(trim($attributes['sku'])),
            'description' => $attributes['description'] ?? null,
            'base_price_minor' => $attributes['base_price_minor'],
            'currency' => strtoupper($attributes['currency']),
            'status' => ProductStatus::Draft,
            'metadata' => $attributes['metadata'] ?? [],
        ]));
    }
}
