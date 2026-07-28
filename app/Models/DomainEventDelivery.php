<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $domain_event_message_id
 * @property int $external_connection_id
 * @property string $status
 * @property int $attempts
 * @property Carbon|null $delivered_at
 * @property-read ExternalConnection $connection
 */
#[Fillable([
    'domain_event_message_id', 'external_connection_id', 'status',
    'attempts', 'delivered_at', 'last_error',
])]
class DomainEventDelivery extends Model
{
    protected function casts(): array
    {
        return ['attempts' => 'integer', 'delivered_at' => 'datetime'];
    }

    public function event(): BelongsTo
    {
        return $this->belongsTo(DomainEventMessage::class, 'domain_event_message_id');
    }

    public function connection(): BelongsTo
    {
        return $this->belongsTo(ExternalConnection::class, 'external_connection_id');
    }
}
