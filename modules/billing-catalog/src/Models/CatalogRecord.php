<?php

declare(strict_types=1);

namespace Liberu\Billing\Catalog\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Liberu\Billing\Catalog\Enums\CatalogStatus;

#[Fillable(['team_id', 'name', 'code', 'description', 'status', 'configuration'])]
abstract class CatalogRecord extends Model
{
    protected function casts(): array
    {
        return ['status' => CatalogStatus::class, 'configuration' => 'array'];
    }
}
