<?php

declare(strict_types=1);

namespace Liberu\Billing\Usage\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['team_id', 'customer_id', 'meter_id', 'event_key', 'quantity', 'unit_price_minor', 'amount_minor', 'currency', 'occurred_at', 'corrects_id', 'metadata'])]
class UsageRecord extends Model
{
    protected $table = 'billing_usage_records';

    protected function casts(): array
    {
        return ['quantity' => 'decimal:4', 'unit_price_minor' => 'integer', 'amount_minor' => 'integer', 'occurred_at' => 'datetime', 'metadata' => 'array'];
    }
}
