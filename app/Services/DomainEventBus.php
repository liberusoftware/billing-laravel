<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\ConnectorType;
use App\Jobs\PublishDomainEvent;
use App\Models\DomainEventMessage;
use App\Models\ExternalConnection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class DomainEventBus
{
    /** @param array<string, mixed> $payload */
    public function record(int $teamId, string $eventName, Model $aggregate, array $payload): DomainEventMessage
    {
        return DB::transaction(function () use ($teamId, $eventName, $aggregate, $payload): DomainEventMessage {
            $event = DomainEventMessage::query()->create([
                'team_id' => $teamId,
                'event_name' => $eventName,
                'aggregate_type' => $aggregate->getMorphClass(),
                'aggregate_id' => (string) $aggregate->getKey(),
                'payload' => $payload,
                'status' => 'pending',
                'occurred_at' => now(),
            ]);

            $connections = ExternalConnection::query()
                ->where('team_id', $teamId)
                ->where('connector_type', ConnectorType::EventBus)
                ->where('is_active', true)
                ->get()
                ->filter(function (ExternalConnection $connection) use ($eventName): bool {
                    $subscriptions = $connection->event_subscriptions ?? [];

                    return in_array('*', $subscriptions, true) || in_array($eventName, $subscriptions, true);
                });

            foreach ($connections as $connection) {
                $event->deliveries()->create(['external_connection_id' => $connection->id]);
            }

            PublishDomainEvent::dispatch($event->id)->afterCommit();

            return $event;
        });
    }

    public function replay(DomainEventMessage $event): DomainEventMessage
    {
        $event->update([
            'status' => 'pending',
            'published_at' => null,
            'last_error' => null,
        ]);
        $event->deliveries()->update([
            'status' => 'pending',
            'delivered_at' => null,
            'last_error' => null,
        ]);
        PublishDomainEvent::dispatch($event->id);

        return $event->refresh();
    }
}
