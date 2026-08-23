<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Models\DomainEventMessage;
use App\Models\ExternalConnection;
use App\Services\Integrations\HttpBusinessConnector;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class HttpBusinessConnectorTest extends TestCase
{
    use RefreshDatabase;

    public function test_push_uses_mapping_bearer_auth_and_hmac_signature(): void
    {
        $connection = ExternalConnection::factory()->create([
            'base_url' => 'https://crm.example.test/api',
            'access_token' => 'trusted-token',
            'signing_secret' => 'trusted-signing-secret',
            'resource_mappings' => ['customers' => '/v2/accounts'],
        ]);
        Http::fake(['*' => Http::response(['id' => 'remote-42'], 201)]);
        $connector = app(HttpBusinessConnector::class);

        $remoteId = $connector->push($connection, 'customers', '42', [
            'name' => 'Acme',
            'email' => 'billing@acme.test',
        ]);

        $this->assertSame('remote-42', $remoteId);
        Http::assertSent(function ($request): bool {
            $body = $request->body();
            $expected = 'sha256='.hash_hmac('sha256', $body, 'trusted-signing-secret');

            return $request->url() === 'https://crm.example.test/api/v2/accounts'
                && $request->hasHeader('Authorization', 'Bearer trusted-token')
                && $request->hasHeader('X-Integration-Signature', $expected)
                && $request['external_reference'] === '42';
        });
    }

    public function test_pull_returns_items_and_cursor(): void
    {
        $connection = ExternalConnection::factory()->create([
            'base_url' => 'https://accounting.example.test/api',
        ]);
        Http::fake(['*' => Http::response([
            'items' => [['id' => 'inv-1']],
            'next_cursor' => 'page-2',
        ])]);

        $result = app(HttpBusinessConnector::class)->pull($connection, 'invoices', 'page-1');

        $this->assertSame('inv-1', $result['items'][0]['id']);
        $this->assertSame('page-2', $result['cursor']);
    }

    public function test_event_delivery_sends_idempotency_key(): void
    {
        $connection = ExternalConnection::factory()->create();
        $event = DomainEventMessage::query()->create([
            'team_id' => $connection->team_id,
            'event_name' => 'invoice.paid',
            'aggregate_type' => 'invoice',
            'aggregate_id' => '10',
            'payload' => ['amount' => 100],
            'occurred_at' => now(),
        ]);
        Http::fake(['*' => Http::response([], 202)]);

        app(HttpBusinessConnector::class)->publish($connection, $event);

        Http::assertSent(fn ($request): bool => $request->hasHeader('Idempotency-Key', $event->id)
            && $request['name'] === 'invoice.paid');
    }
}
