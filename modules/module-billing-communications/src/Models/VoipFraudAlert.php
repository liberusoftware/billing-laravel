<?php

declare(strict_types=1);

namespace Liberu\Billing\Communications\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

final class VoipFraudAlert extends Model
{
    protected $table = 'voip_fraud_alerts';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['context' => 'array', 'resolved_at' => 'datetime'];
    }

    public function scopeForTeam(Builder $query, int $teamId): Builder
    {
        return $query->where('team_id', $teamId);
    }
}
