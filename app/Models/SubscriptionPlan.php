<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use Override;

/**
 * @property int $id
 * @property string $name
 * @property string $code
 * @property string|null $description
 * @property string $price
 * @property string $currency
 * @property array|null $features
 * @property bool $is_active
 * @property int $trial_days
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Collection<int, Subscription> $subscriptions
 * @property-read string $formatted_price
 */
#[Fillable([
    'name',
    'code',
    'description',
    'price',
    'currency',
    'features',
    'is_active',
    'trial_days',
])]
class SubscriptionPlan extends Model
{
    #[Override]
    protected function casts(): array
    {

        return [
            'features' => 'array',
            'is_active' => 'boolean',
            'trial_days' => 'integer',
            'price' => 'decimal:2',
        ];

    }

    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class);
    }

    protected function formattedPrice(): Attribute
    {
        return Attribute::make(
            get: fn (): string => number_format(
                (float) $this->price,
                2
            ).' '.$this->currency
        );
    }
}
