<?php

declare(strict_types=1);

namespace App\Models;

use App\Traits\HasTeam;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'team_id', 'voip_account_id', 'call_detail_record_id', 'rule',
    'severity', 'message', 'context', 'resolved_at',
])]
class VoipFraudAlert extends Model
{
    use HasTeam;

    protected function casts(): array
    {
        return ['context' => 'array', 'resolved_at' => 'datetime'];
    }

    public function voipAccount(): BelongsTo
    {
        return $this->belongsTo(VoipAccount::class);
    }

    public function callDetailRecord(): BelongsTo
    {
        return $this->belongsTo(CallDetailRecord::class);
    }
}
