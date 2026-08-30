<?php

declare(strict_types=1);

namespace Liberu\Billing\Hosting\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

final class HostingServer extends Model
{
    use SoftDeletes;

    protected $table = 'billing_hosting_servers';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['api_token' => 'encrypted', 'is_active' => 'boolean', 'metadata' => 'array'];
    }

    public function scopeForTeam(Builder $query, int $teamId): Builder
    {
        return $query->where('team_id', $teamId);
    }
}
