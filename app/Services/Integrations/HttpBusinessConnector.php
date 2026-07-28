<?php

declare(strict_types=1);

namespace App\Services\Integrations;

use App\Contracts\BusinessConnector;
use App\Models\DomainEventMessage;
use App\Models\ExternalConnection;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use RuntimeException;
use Throwable;

class HttpBusinessConnector implements BusinessConnector
{
    public function push(
        ExternalConnection $connection,
        string $resource,
        string $localId,
        array $payload,
        ?string $remoteId = null
    ): string {
        $path = $this->resourcePath($connection, $resource);
        $method = $remoteId === null ? 'post' : 'put';
        $target = $remoteId === null ? $path : $path.'/'.rawurlencode($remoteId);
        $response = $this->send($connection, $method, $target, [
            ...$payload,
            'external_reference' => $localId,
        ]);
        $id = $response->json('id') ?? $response->json('data.id') ?? $remoteId;

        if (! is_string($id) && ! is_int($id)) {
            throw new RuntimeException('The connector response did not contain a resource identifier.');
        }

        return (string) $id;
    }

    public function pull(ExternalConnection $connection, string $resource, ?string $cursor = null): array
    {
        $query = $cursor !== null ? '?cursor='.rawurlencode($cursor) : '';
        $body = $this->send($connection, 'get', $this->resourcePath($connection, $resource).$query)->json();
        if (! is_array($body)) {
            throw new RuntimeException('The connector returned an invalid response.');
        }
        $items = $body['items'] ?? $body['data'] ?? $body;

        if (! is_array($items)) {
            throw new RuntimeException('The connector returned an invalid resource collection.');
        }

        return [
            'items' => array_values(array_filter($items, 'is_array')),
            'cursor' => isset($body['next_cursor']) && is_string($body['next_cursor'])
                ? $body['next_cursor']
                : null,
        ];
    }

    public function publish(ExternalConnection $connection, DomainEventMessage $event): void
    {
        $this->send($connection, 'post', '/events', [
            'id' => $event->id,
            'name' => $event->event_name,
            'aggregate_type' => $event->aggregate_type,
            'aggregate_id' => $event->aggregate_id,
            'occurred_at' => $event->occurred_at->toISOString(),
            'payload' => $event->payload,
        ], ['Idempotency-Key' => $event->id]);
    }

    private function resourcePath(ExternalConnection $connection, string $resource): string
    {
        $mapping = $connection->resource_mappings[$resource] ?? null;

        return is_string($mapping) && str_starts_with($mapping, '/')
            ? $mapping
            : '/'.str_replace('_', '-', $resource);
    }

    /**
     * @param  array<string, mixed>  $payload
     * @param  array<string, string>  $headers
     */
    private function send(
        ExternalConnection $connection,
        string $method,
        string $path,
        array $payload = [],
        array $headers = []
    ): Response {
        $json = json_encode($payload, JSON_THROW_ON_ERROR);
        if ($connection->signing_secret !== null && $connection->signing_secret !== '') {
            $headers['X-Integration-Signature'] = 'sha256='.hash_hmac('sha256', $json, $connection->signing_secret);
        }

        try {
            $request = $this->request($connection)->withHeaders($headers);

            return $method === 'get'
                ? $request->get($path)->throw()
                : $request->withBody($json, 'application/json')->send($method, $path)->throw();
        } catch (Throwable $exception) {
            report($exception);

            throw new RuntimeException("{$connection->provider} connector operation failed.");
        }
    }

    private function request(ExternalConnection $connection): PendingRequest
    {
        return Http::baseUrl(rtrim($connection->base_url, '/'))
            ->withToken($connection->access_token)
            ->acceptJson()
            ->timeout(20)
            ->retry(3, 250);
    }
}
