<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use Override;

/**
 * @property int $id
 * @property int|null $team_id
 * @property int $created_by
 * @property string $name
 * @property string $subject
 * @property string $content
 * @property array|null $recipient_filters
 * @property string $status
 * @property int $total_recipients
 * @property int $sent_count
 * @property int $failed_count
 * @property Carbon|null $scheduled_at
 * @property Carbon|null $started_sending_at
 * @property Carbon|null $completed_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Team|null $team
 * @property-read User|null $creator
 * @property-read Collection<int, EmailCampaignStat> $emailStats
 */
#[Fillable([
    'team_id',
    'created_by',
    'name',
    'subject',
    'content',
    'recipient_filters',
    'status',
    'total_recipients',
    'sent_count',
    'failed_count',
    'scheduled_at',
    'started_sending_at',
    'completed_at',
])]
class EmailCampaign extends Model
{
    #[Override]
    protected function casts(): array
    {

        return [
            'recipient_filters' => 'array',
            'scheduled_at' => 'datetime',
            'started_sending_at' => 'datetime',
            'completed_at' => 'datetime',
        ];

    }

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'created_by'
        );
    }

    public function emailStats(): HasMany
    {
        return $this->hasMany(EmailCampaignStat::class);
    }

    public function markAsSending(): void
    {
        $this->update(
            [
                'status' => 'sending',
                'started_sending_at' => now(),
            ]
        );
    }

    public function markAsSent(): void
    {
        $this->update(
            [
                'status' => 'sent',
                'completed_at' => now(),
            ]
        );
    }

    public function incrementSent(): void
    {
        $this->increment('sent_count');
    }

    public function incrementFailed(): void
    {
        $this->increment('failed_count');
    }

    public function getProgressPercentage(): float
    {
        if ($this->total_recipients === 0) {
            return 0;
        }

        $total = $this->sent_count + $this->failed_count;

        return round(
            ($total / $this->total_recipients) * 100,
            2
        );
    }
}
