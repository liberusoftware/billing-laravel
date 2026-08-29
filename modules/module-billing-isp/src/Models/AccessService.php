<?php

declare(strict_types=1);

namespace Liberu\Billing\Isp\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

final class AccessService extends Model
{
    use SoftDeletes;

    protected $table = 'billing_isp_records';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['metadata' => 'array', 'monthly_data_limit_bytes' => 'integer', 'current_period_usage_bytes' => 'integer', 'activated_at' => 'datetime', 'suspended_at' => 'datetime', 'radius_synced_at' => 'datetime'];
    }

    public function scopeForTeam(Builder $query, int $teamId): Builder
    {
        return $query->where('team_id', $teamId);
    }
}
