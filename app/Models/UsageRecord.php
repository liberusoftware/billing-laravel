<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use Override;

/**
 * @property int $id
 * @property int|null $subscription_id
 * @property string $metric_name
 * @property string $quantity
 * @property Carbon $recorded_at
 * @property bool $processed
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Subscription|null $subscription
 */
#[Fillable([
    'subscription_id',
    'metric_name',
    'quantity',
    'recorded_at',
    'processed',
])]
class UsageRecord extends Model
{
    #[Override]
    protected function casts(): array
    {
        return [
            'recorded_at' => 'datetime',
            'processed' => 'boolean',
            'quantity' => 'decimal:2',
        ];
    }

    public function subscription(): BelongsTo
    {
        return $this->belongsTo(Subscription::class);
    }
}
