<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use Override;

/**
 * @property int $id
 * @property string $name
 * @property string $slug
 * @property string|null $description
 * @property bool $is_active
 * @property array|null $config
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Collection<int, Order> $orders
 */
#[Fillable([
    'name',
    'slug',
    'description',
    'is_active',
    'config',
])]
class OrderFormTemplate extends Model
{
    use HasFactory;

    #[Override]
    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'config' => 'array',
        ];
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    /**
     * Subscription plan ids this order form offers.
     *
     * @return array<int, int>
     */
    public function offeredPlanIds(): array
    {
        return array_map('intval', $this->config['plan_ids'] ?? []);
    }

    public function offersPlan(int $planId): bool
    {
        return in_array($planId, $this->offeredPlanIds(), true);
    }
}
