<?php

declare(strict_types=1);

namespace Liberu\Billing\Communications\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

final class CallDetailRecord extends Model
{
    protected $table = 'call_detail_records';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['started_at' => 'datetime', 'answered_at' => 'datetime', 'ended_at' => 'datetime', 'duration_seconds' => 'integer', 'billable_seconds' => 'integer', 'rated_cost' => 'decimal:4', 'invoiced_at' => 'datetime', 'metadata' => 'array'];
    }

    public function scopeForTeam(Builder $query, int $teamId): Builder
    {
        return $query->where('team_id', $teamId);
    }
}
