<?php

declare(strict_types=1);

namespace App\Models;

use App\Traits\HasTeam;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property string $id
 * @property int $team_id
 * @property string $event_name
 * @property string $aggregate_type
 * @property string $aggregate_id
 * @property array<string, mixed> $payload
 * @property string $status
 * @property int $attempts
 * @property Carbon $occurred_at
 * @property Carbon|null $published_at
 * @property string|null $last_error
 */
#[Fillable([
    'id', 'team_id', 'event_name', 'aggregate_type', 'aggregate_id',
    'payload', 'status', 'attempts', 'occurred_at', 'published_at', 'last_error',
])]
class DomainEventMessage extends Model
{
    use HasTeam;
    use HasUuids;

    protected function casts(): array
    {
        return [
            'payload' => 'array',
            'attempts' => 'integer',
            'occurred_at' => 'datetime',
            'published_at' => 'datetime',
        ];
    }

    /** @return HasMany<DomainEventDelivery, $this> */
    public function deliveries(): HasMany
    {
        return $this->hasMany(DomainEventDelivery::class);
    }
}
