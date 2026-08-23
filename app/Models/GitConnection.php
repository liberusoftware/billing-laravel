<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\GitProvider;
use App\Traits\HasTeam;
use Database\Factories\GitConnectionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $team_id
 * @property GitProvider $provider
 * @property string $name
 * @property string $base_url
 * @property string $access_token
 * @property string|null $webhook_secret
 * @property bool $is_active
 * @property Carbon|null $last_synced_at
 */
#[Fillable([
    'team_id', 'provider', 'name', 'base_url', 'access_token', 'webhook_secret',
    'is_active', 'last_synced_at',
])]
class GitConnection extends Model
{
    use HasFactory;
    use HasTeam;

    protected $hidden = ['access_token', 'webhook_secret'];

    protected static function newFactory(): GitConnectionFactory
    {
        return GitConnectionFactory::new();
    }

    protected function casts(): array
    {
        return [
            'provider' => GitProvider::class,
            'access_token' => 'encrypted',
            'webhook_secret' => 'encrypted',
            'is_active' => 'boolean',
            'last_synced_at' => 'datetime',
        ];
    }

    public function repositories(): HasMany
    {
        return $this->hasMany(GitRepository::class);
    }
}
