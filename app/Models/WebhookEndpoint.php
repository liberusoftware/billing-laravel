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
 * @property string $url
 * @property string|null $secret
 * @property array|null $events
 * @property bool $is_active
 * @property string|null $description
 * @property int $max_retries
 * @property int $retry_interval
 * @property Carbon|null $last_triggered_at
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property-read Team|null $team
 * @property-read Collection<int, WebhookEvent> $webhookEvents
 */
#[Fillable([
    'team_id',
    'url',
    'secret',
    'events',
    'is_active',
    'description',
    'max_retries',
    'retry_interval',
    'last_triggered_at',
])]
class WebhookEndpoint extends Model
{
    /**
     * The signing secret must never be serialized into API responses.
     *
     * @var list<string>
     */
    protected $hidden = ['secret'];

    #[Override]
    protected function casts(): array
    {
        return [
            'events' => 'array',
            'is_active' => 'boolean',
            'last_triggered_at' => 'datetime',
            'secret' => 'encrypted',
        ];
    }

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    public function webhookEvents(): HasMany
    {
        return $this->hasMany(WebhookEvent::class);
    }

    public function isSubscribedTo(string $eventType): bool
    {
        if (! $this->is_active) {
            return false;
        }

        if (empty($this->events)) {
            return true; // Subscribe to all events if none specified
        }

        return in_array(
            $eventType,
            $this->events
        );
    }
}
