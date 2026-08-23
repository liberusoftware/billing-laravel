<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $external_connection_id
 * @property string $resource_type
 * @property string $local_id
 * @property string $remote_id
 * @property string|null $checksum
 * @property string $status
 * @property Carbon|null $last_pushed_at
 * @property Carbon|null $last_pulled_at
 */
#[Fillable([
    'external_connection_id', 'resource_type', 'local_id', 'remote_id',
    'checksum', 'status', 'last_pushed_at', 'last_pulled_at', 'metadata',
])]
class ExternalSyncRecord extends Model
{
    protected function casts(): array
    {
        return ['last_pushed_at' => 'datetime', 'last_pulled_at' => 'datetime', 'metadata' => 'array'];
    }

    public function connection(): BelongsTo
    {
        return $this->belongsTo(ExternalConnection::class, 'external_connection_id');
    }
}
