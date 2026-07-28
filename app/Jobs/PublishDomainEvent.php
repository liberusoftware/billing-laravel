<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Contracts\BusinessConnector;
use App\Models\DomainEventMessage;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Throwable;

class PublishDomainEvent implements ShouldQueue
{
    use Queueable;

    public int $tries = 5;

    public function __construct(public readonly string $eventId) {}

    public function handle(BusinessConnector $connector): void
    {
        $event = DomainEventMessage::query()->with('deliveries.connection')->findOrFail($this->eventId);
        $event->increment('attempts');
        $failed = false;

        foreach ($event->deliveries()->where('status', '!=', 'delivered')->get() as $delivery) {
            try {
                $connector->publish($delivery->connection, $event);
                $delivery->update([
                    'status' => 'delivered',
                    'attempts' => $delivery->attempts + 1,
                    'delivered_at' => now(),
                    'last_error' => null,
                ]);
            } catch (Throwable $exception) {
                report($exception);
                $failed = true;
                $delivery->update([
                    'status' => 'failed',
                    'attempts' => $delivery->attempts + 1,
                    'last_error' => 'Event delivery failed.',
                ]);
            }
        }

        $event->update([
            'status' => $failed ? 'failed' : 'published',
            'published_at' => $failed ? null : now(),
            'last_error' => $failed ? 'One or more event deliveries failed.' : null,
        ]);
    }
}
