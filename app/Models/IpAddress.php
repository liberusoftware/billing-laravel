<?php

declare(strict_types=1);

namespace App\Models;

use App\Traits\HasTeam;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

#[Fillable([
    'team_id', 'ip_pool_id', 'address', 'status', 'assignable_type', 'assignable_id',
    'hostname', 'assigned_at', 'released_at', 'notes',
])]
class IpAddress extends Model
{
    use HasTeam;

    protected function casts(): array
    {
        return ['assigned_at' => 'datetime', 'released_at' => 'datetime'];
    }

    public function pool(): BelongsTo
    {
        return $this->belongsTo(IpPool::class, 'ip_pool_id');
    }

    public function assignable(): MorphTo
    {
        return $this->morphTo();
    }
}
