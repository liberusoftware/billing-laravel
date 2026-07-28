<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property int $git_connection_id
 * @property string $external_id
 * @property string $full_name
 * @property string $default_branch
 * @property string $web_url
 * @property bool $is_private
 * @property-read GitConnection $connection
 */
#[Fillable([
    'git_connection_id', 'external_id', 'full_name', 'default_branch', 'web_url',
    'is_private', 'external_updated_at', 'last_synced_at',
])]
class GitRepository extends Model
{
    protected function casts(): array
    {
        return [
            'is_private' => 'boolean',
            'external_updated_at' => 'datetime',
            'last_synced_at' => 'datetime',
        ];
    }

    public function connection(): BelongsTo
    {
        return $this->belongsTo(GitConnection::class, 'git_connection_id');
    }

    /** @return HasMany<GitSyncRecord, $this> */
    public function syncRecords(): HasMany
    {
        return $this->hasMany(GitSyncRecord::class);
    }

    /** @return HasMany<GitRelease, $this> */
    public function releases(): HasMany
    {
        return $this->hasMany(GitRelease::class);
    }
}
