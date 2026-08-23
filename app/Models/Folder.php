<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $name
 * @property int|null $parent_id
 * @property int $user_id
 * @property int|null $team_id
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read User|null $user
 * @property-read Folder|null $parent
 * @property-read Collection<int, Folder> $children
 * @property-read Collection<int, File> $files
 * @property-read Team|null $team
 */
#[Fillable([
    'name',
    'parent_id',
    'user_id',
    'team_id',
])]
class Folder extends Model
{
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

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

    public function files(): HasMany
    {
        return $this->hasMany(File::class);
    }

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }
}
