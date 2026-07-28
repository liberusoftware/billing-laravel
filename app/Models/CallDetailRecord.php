<?php

declare(strict_types=1);

namespace App\Models;

use App\Traits\HasTeam;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'team_id', 'voip_account_id', 'call_rate_rule_id', 'external_id', 'source',
    'destination', 'direction', 'started_at', 'answered_at', 'ended_at',
    'duration_seconds', 'billable_seconds', 'rated_cost', 'currency',
    'disposition', 'invoiced_at', 'metadata',
])]
class CallDetailRecord extends Model
{
    use HasTeam;

    protected function casts(): array
    {
        return [
            'started_at' => 'datetime',
            'answered_at' => 'datetime',
            'ended_at' => 'datetime',
            'duration_seconds' => 'integer',
            'billable_seconds' => 'integer',
            'rated_cost' => 'decimal:4',
            'invoiced_at' => 'datetime',
            'metadata' => 'array',
        ];
    }

    public function voipAccount(): BelongsTo
    {
        return $this->belongsTo(VoipAccount::class);
    }

    public function rateRule(): BelongsTo
    {
        return $this->belongsTo(CallRateRule::class, 'call_rate_rule_id');
    }
}
