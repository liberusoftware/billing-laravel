<?php

declare(strict_types=1);

namespace Liberu\Billing\Communications\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

final class VoipAccount extends Model
{
    protected $table = 'voip_accounts';

    protected $guarded = ['id'];

    protected $hidden = ['sip_secret'];

    protected function casts(): array
    {
        return [
            'sip_secret' => 'encrypted',
            'credit_limit' => 'decimal:4',
            'current_usage_cost' => 'decimal:4',
            'max_concurrent_calls' => 'integer',
            'international_enabled' => 'boolean',
            'provisioned_at' => 'datetime',
            'platform_synced_at' => 'datetime',
        ];
    }

    public function scopeForTeam(Builder $query, int $teamId): Builder
    {
        return $query->where('team_id', $teamId);
    }
}
