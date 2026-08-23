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
 * @property string|null $external_id
 * @property string $version
 * @property string $name
 * @property string|null $changelog
 * @property Carbon|null $released_at
 * @property Carbon|null $deployed_at
 */
#[Fillable([
    'git_repository_id', 'external_id', 'version', 'name', 'changelog', 'state',
    'web_url', 'deployment_environment', 'deployment_status', 'released_at', 'deployed_at',
])]
class GitRelease extends Model
{
    protected function casts(): array
    {
        return ['released_at' => 'datetime', 'deployed_at' => 'datetime'];
    }

    public function repository(): BelongsTo
    {
        return $this->belongsTo(GitRepository::class, 'git_repository_id');
    }
}
