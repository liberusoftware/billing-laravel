<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $git_repository_id
 * @property string $record_type
 * @property string $external_id
 * @property string|null $title
 * @property string|null $author
 * @property Carbon|null $external_created_at
 */
#[Fillable([
    'git_repository_id', 'record_type', 'external_id', 'title', 'state',
    'web_url', 'author', 'external_created_at', 'external_updated_at', 'payload',
])]
class GitSyncRecord extends Model
{
    protected function casts(): array
    {
        return [
            'external_created_at' => 'datetime',
            'external_updated_at' => 'datetime',
            'payload' => 'array',
        ];
    }

    public function repository(): BelongsTo
    {
        return $this->belongsTo(GitRepository::class, 'git_repository_id');
    }
}
