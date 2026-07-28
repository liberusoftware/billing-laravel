<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\RadiusSessionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $isp_service_id
 * @property string $accounting_session_id
 * @property int $input_bytes
 * @property int $output_bytes
 * @property int $session_seconds
 * @property Carbon $started_at
 * @property Carbon|null $ended_at
 * @property-read int $total_bytes
 */
#[Fillable([
    'isp_service_id',
    'accounting_session_id',
    'nas_identifier',
    'ip_address',
    'started_at',
    'ended_at',
    'input_bytes',
    'output_bytes',
    'session_seconds',
])]
class RadiusSession extends Model
{
    use HasFactory;

    protected static function newFactory(): RadiusSessionFactory
    {
        return RadiusSessionFactory::new();
    }

    protected function casts(): array
    {
        return [
            'started_at' => 'datetime',
            'ended_at' => 'datetime',
            'input_bytes' => 'integer',
            'output_bytes' => 'integer',
            'session_seconds' => 'integer',
        ];
    }

    public function ispService(): BelongsTo
    {
        return $this->belongsTo(IspService::class);
    }

    public function getTotalBytesAttribute(): int
    {
        return $this->input_bytes + $this->output_bytes;
    }
}
