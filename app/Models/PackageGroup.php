<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Carbon;
use Override;

/**
 * @property int $id
 * @property int|null $team_id
 * @property string $name
 * @property string|null $description
 * @property int $sort_order
 * @property bool $is_active
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Team|null $team
 * @property-read Collection<int, SubscriptionPlan> $packages
 */
#[Fillable([
    'team_id',
    'name',
    'description',
    'sort_order',
    'is_active',
])]
class PackageGroup extends Model
{
    #[Override]
    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    public function packages(): BelongsToMany
    {
        return $this->belongsToMany(
            SubscriptionPlan::class,
            'package_group_items',
            'package_group_id',
            'subscription_plan_id'
        )->withPivot('sort_order')->orderByPivot('sort_order');
    }
}
