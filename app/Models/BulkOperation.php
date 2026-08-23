<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use Override;

/**
 * @property int $id
 * @property int $user_id
 * @property int|null $team_id
 * @property string $type
 * @property array|null $parameters
 * @property string $status
 * @property int $total_items
 * @property int $processed_items
 * @property int $failed_items
 * @property string|null $error_message
 * @property string|null $result_file
 * @property Carbon|null $started_at
 * @property Carbon|null $completed_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read User|null $user
 * @property-read Team|null $team
 */
#[Fillable([
    'user_id',
    'team_id',
    'type',
    'parameters',
    'status',
    'total_items',
    'processed_items',
    'failed_items',
    'error_message',
    'result_file',
    'started_at',
    'completed_at',
])]
class BulkOperation extends Model
{
    #[Override]
    protected function casts(): array
    {

        return [
            'parameters' => 'array',
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
        ];

    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    public function markAsProcessing(): void
    {
        $this->update(
            [
                'status' => 'processing',
                'started_at' => now(),
            ]
        );
    }

    public function markAsCompleted(): void
    {
        $this->update(
            [
                'status' => 'completed',
                'completed_at' => now(),
            ]
        );
    }

    public function markAsFailed(string $errorMessage): void
    {
        $this->update(
            [
                'status' => 'failed',
                'error_message' => $errorMessage,
                'completed_at' => now(),
            ]
        );
    }

    public function incrementProcessed(): void
    {
        $this->increment('processed_items');
    }

    public function incrementFailed(): void
    {
        $this->increment('failed_items');
    }

    public function getProgressPercentage(): float
    {
        if ($this->total_items === 0) {
            return 0;
        }

        return round(
            ($this->processed_items / $this->total_items) * 100,
            2
        );
    }
}
