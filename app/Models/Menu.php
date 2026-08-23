<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $name
 * @property string $url
 * @property int|null $parent_id
 * @property int $order
 * @property int|null $team_id
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 * @property-read Menu|null $parent
 * @property-read Collection<int, Menu> $children
 */
#[Fillable([
    'name',
    'url',
    'parent_id',
    'order',
])]
class Menu extends Model
{
    use HasFactory, SoftDeletes;

    public function parent(): BelongsTo
    {
        return $this->belongsTo(
            __CLASS__,
            'parent_id'
        );
    }

    public function children(): HasMany
    {
        return $this->hasMany(
            __CLASS__,
            'parent_id'
        );
    }
}
