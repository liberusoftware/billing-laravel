<?php

declare(strict_types=1);

namespace Liberu\Billing\Usage\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['team_id', 'name', 'code', 'unit', 'unit_price_minor', 'currency', 'threshold', 'active', 'metadata'])]
class Meter extends Model
{
    protected $table = 'billing_usage_meters';

    protected function casts(): array
    {
        return ['unit_price_minor' => 'integer', 'threshold' => 'decimal:4', 'active' => 'boolean', 'metadata' => 'array'];
    }
}
