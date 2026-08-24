<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Contracts\BusinessConnector;
use App\Enums\ConnectorType;
use App\Jobs\PublishDomainEvent;
use App\Models\Customer;
use App\Models\DomainEventMessage;
use App\Models\ExternalConnection;
use App\Models\Team;
use App\Services\DomainEventBus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class DomainEventBusTest extends TestCase
{
    use RefreshDatabase;

    public function test_event_is_recorded_and_only_subscribed_connections_receive_delivery(): void
    {
        Queue::fake();
        $team = Team::factory()->create();
        $customer = Customer::factory()->create(['team_id' => $team->id]);
        $subscribed = ExternalConnection::factory()->create([
            'team_id' => $customer->team_id,
            'connector_type' => ConnectorType::EventBus,
            'event_subscriptions' => ['customer.created'],
        ]);
        ExternalConnection::factory()->create([
            'team_id' => $customer->team_id,
            'connector_type' => ConnectorType::EventBus,
            'event_subscriptions' => ['invoice.paid'],
        ]);

        $event = app(DomainEventBus::class)->record(
            (int) $customer->team_id,
            'customer.created',
            $customer,
            ['email' => $customer->email]
        );

        $this->assertDatabaseHas('domain_event_deliveries', [
            'domain_event_message_id' => $event->id,
            'external_connection_id' => $subscribed->id,
        ]);
        $this->assertDatabaseCount('domain_event_deliveries', 1);
        Queue::assertPushed(PublishDomainEvent::class, fn ($job): bool => $job->eventId === $event->id);
    }

    public function test_job_publishes_idempotent_deliveries_and_replay_requeues_event(): void
    {
        Queue::fake();
        $team = Team::factory()->create();
        $customer = Customer::factory()->create(['team_id' => $team->id]);
        ExternalConnection::factory()->create([
            'team_id' => $customer->team_id,
            'connector_type' => ConnectorType::EventBus,
            'event_subscriptions' => ['*'],
        ]);
        $event = app(DomainEventBus::class)->record(
            (int) $customer->team_id,
            'customer.updated',
            $customer,
            ['name' => $customer->name]
        );
        $connector = new EventRecordingConnector();

        (new PublishDomainEvent($event->id))->handle($connector);

        $this->assertCount(1, $connector->published);
        $this->assertSame('published', $event->refresh()->status);
        $this->assertNotNull($event->published_at);
        $this->assertDatabaseHas('domain_event_deliveries', ['status' => 'delivered']);

        app(DomainEventBus::class)->replay($event);
        $this->assertSame('pending', $event->refresh()->status);
        Queue::assertPushed(PublishDomainEvent::class);
    }
}

final class EventRecordingConnector implements BusinessConnector
{
    /** @var list<string> */
    public array $published = [];

    public function push(
        ExternalConnection $connection,
        string $resource,
        string $localId,
        array $payload,
        ?string $remoteId = null
    ): string {
        return 'unused';
    }

    public function pull(ExternalConnection $connection, string $resource, ?string $cursor = null): array
    {
        return ['items' => [], 'cursor' => null];
    }

    public function publish(ExternalConnection $connection, DomainEventMessage $event): void
    {
        $this->published[] = $event->id;
    }
}
