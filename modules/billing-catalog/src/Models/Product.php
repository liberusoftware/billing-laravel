<?php

declare(strict_types=1);

namespace Liberu\Billing\Catalog\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Liberu\Billing\Catalog\Enums\ProductStatus;

#[Fillable(['team_id', 'name', 'sku', 'description', 'base_price_minor', 'currency', 'status', 'metadata'])]
class Product extends Model
{
    protected $table = 'billing_catalog_products';

    protected function casts(): array
    {
        return [
            'base_price_minor' => 'integer',
            'status' => ProductStatus::class,
            'metadata' => 'array',
        ];
    }
}
