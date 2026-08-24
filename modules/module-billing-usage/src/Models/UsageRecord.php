<?php

declare(strict_types=1);

namespace Liberu\Billing\Usage\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int|null $team_id
 * @property int|null $customer_id
 * @property int $meter_id
 * @property string $event_key
 * @property string $quantity
 * @property int $unit_price_minor
 * @property int $amount_minor
 * @property string $currency
 * @property Carbon|null $occurred_at
 * @property int|null $corrects_id
 */
#[Fillable(['team_id', 'customer_id', 'meter_id', 'event_key', 'quantity', 'unit_price_minor', 'amount_minor', 'currency', 'occurred_at', 'corrects_id', 'metadata'])]
class UsageRecord extends Model
{
    protected $table = 'billing_usage_records';

    protected function casts(): array
    {
        return ['quantity' => 'decimal:4', 'unit_price_minor' => 'integer', 'amount_minor' => 'integer', 'occurred_at' => 'datetime', 'metadata' => 'array'];
    }
}
