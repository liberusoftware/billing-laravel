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
 * @property string $path
 * @property string $mime_type
 * @property int $size
 * @property int|null $folder_id
 * @property int|null $user_id
 * @property int|null $team_id
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read User|null $user
 * @property-read Folder|null $folder
 * @property-read Team|null $team
 * @property-read Collection<int, FileShare> $shares
 */
#[Fillable([
    'name',
    'path',
    'mime_type',
    'size',
    'folder_id',
    'user_id',
    'team_id',
])]
class File extends Model
{
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function folder(): BelongsTo
    {
        return $this->belongsTo(Folder::class);
    }

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    public function shares(): HasMany
    {
        return $this->hasMany(FileShare::class);
    }
}
