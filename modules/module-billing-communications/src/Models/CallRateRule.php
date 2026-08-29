<?php

declare(strict_types=1);

namespace Liberu\Billing\Communications\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

final class CallRateRule extends Model
{
    protected $table = 'call_rate_rules';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['connection_fee' => 'decimal:4', 'rate_per_minute' => 'decimal:4', 'billing_increment_seconds' => 'integer', 'is_active' => 'boolean'];
    }

    public function scopeForTeam(Builder $query, int $teamId): Builder
    {
        return $query->where('team_id', $teamId);
    }
}
