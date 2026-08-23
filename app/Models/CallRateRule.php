<?php

declare(strict_types=1);

namespace App\Models;

use App\Traits\HasTeam;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable([
    'team_id', 'name', 'destination_prefix', 'connection_fee', 'rate_per_minute',
    'billing_increment_seconds', 'currency', 'is_active',
])]
class CallRateRule extends Model
{
    use HasTeam;

    protected function casts(): array
    {
        return [
            'connection_fee' => 'decimal:4',
            'rate_per_minute' => 'decimal:4',
            'billing_increment_seconds' => 'integer',
            'is_active' => 'boolean',
        ];
    }
}
