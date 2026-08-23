<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use Override;

/**
 * @property int $id
 * @property int $webhook_endpoint_id
 * @property string $event_type
 * @property array $payload
 * @property string $status
 * @property int $attempts
 * @property string|null $last_error
 * @property Carbon|null $sent_at
 * @property Carbon|null $next_retry_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read WebhookEndpoint|null $webhookEndpoint
 */
#[Fillable([
    'webhook_endpoint_id',
    'event_type',
    'payload',
    'status',
    'attempts',
    'last_error',
    'sent_at',
    'next_retry_at',
])]
class WebhookEvent extends Model
{
    #[Override]
    protected function casts(): array
    {
        return [
            'payload' => 'array',
            'sent_at' => 'datetime',
            'next_retry_at' => 'datetime',
        ];
    }

    public function webhookEndpoint(): BelongsTo
    {
        return $this->belongsTo(WebhookEndpoint::class);
    }

    public function markAsSent(): void
    {
        $this->update(
            [
                'status' => 'sent',
                'sent_at' => now(),
                'next_retry_at' => null,
            ]
        );
    }

    public function markAsFailed(string $error, int $retryIntervalSeconds = 60): void
    {
        // Exponential backoff: each retry waits interval * 2^(prior attempts).
        $delaySeconds = $retryIntervalSeconds * (2 ** $this->attempts);

        $this->update(
            [
                'status' => 'failed',
                'last_error' => $error,
                'attempts' => $this->attempts + 1,
                'next_retry_at' => now()->addSeconds($delaySeconds),
            ]
        );
    }

    public function shouldRetry(int $maxRetries): bool
    {
        return $this->attempts < $maxRetries;
    }
}
