<?php

declare(strict_types=1);

namespace Liberu\Billing\Hosting\Support;

use GuzzleHttp\Client;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Contracts\Support\Arrayable;
use InvalidArgumentException;
use RuntimeException;

abstract class AbstractControlPanelDriver
{
    protected ClientInterface $client;

    public function __construct(?ClientInterface $client = null)
    {
        $this->client = $client ?? new Client();
    }

    /** @param array<string, mixed> $attributes @return array<string, mixed> */
    protected function server(array $attributes): array
    {
        $server = $attributes['server'] ?? $attributes;
        if ($server instanceof Arrayable) {
            $server = $server->toArray();
        }
        if (! is_array($server)) {
            throw new InvalidArgumentException('Control-panel server configuration is required.');
        }

        return $server;
    }

    /** @param array<string, mixed> $attributes @return array<string, mixed> */
    protected function account(array $attributes): array
    {
        $account = $attributes['account'] ?? $attributes;
        if ($account instanceof Arrayable) {
            $account = $account->toArray();
        }
        if (! is_array($account)) {
            throw new InvalidArgumentException('Control-panel account data is required.');
        }

        foreach (['username', 'domain'] as $field) {
            if (trim((string) ($account[$field] ?? '')) === '') {
                throw new InvalidArgumentException("Control-panel account field [{$field}] is required.");
            }
        }

        return $account;
    }

    /** @param array<string, mixed> $server */
    protected function baseUrl(array $server): string
    {
        $url = trim((string) ($server['api_url'] ?? ''));
        if ($url === '') {
            $hostname = trim((string) ($server['hostname'] ?? ''));
            if ($hostname === '') {
                throw new InvalidArgumentException('Control-panel server hostname or API URL is required.');
            }
            $url = 'https://'.$hostname;
        }

        return rtrim($url, '/');
    }

    /** @param array<string, mixed> $server */
    protected function token(array $server): string
    {
        $token = trim((string) ($server['api_token'] ?? $server['token'] ?? ''));
        if ($token === '') {
            throw new InvalidArgumentException('Control-panel API credentials are required.');
        }

        return $token;
    }

    /** @param array<string, mixed> $account */
    protected function password(array $account): string
    {
        $password = (string) ($account['password'] ?? '');

        return $password !== '' ? $password : bin2hex(random_bytes(16));
    }

    /** @param array<string, mixed> $server @param array<string, mixed> $options @return array<string, mixed> */
    protected function requestJson(string $method, string $uri, array $server, array $options = []): array
    {
        try {
            $response = $this->client->request($method, $this->baseUrl($server).'/'.ltrim($uri, '/'), array_replace_recursive([
                'http_errors' => false,
                'timeout' => (float) ($server['timeout'] ?? 30),
                'verify' => (bool) ($server['verify'] ?? true),
                'headers' => ['Accept' => 'application/json'],
            ], $options));
        } catch (GuzzleException $exception) {
            throw new RuntimeException('Control-panel API request failed.', 0, $exception);
        }

        $status = $response->getStatusCode();
        $body = (string) $response->getBody();
        $decoded = json_decode($body, true);
        if ($status < 200 || $status >= 300) {
            throw new RuntimeException("Control-panel API returned HTTP {$status}.");
        }
        if (! is_array($decoded)) {
            throw new RuntimeException('Control-panel API returned invalid JSON.');
        }

        return $decoded;
    }

    /** @param array<string, mixed> $server @param array<string, mixed> $options @return array<string, string> */
    protected function requestForm(string $method, string $uri, array $server, array $options = []): array
    {
        try {
            $response = $this->client->request($method, $this->baseUrl($server).'/'.ltrim($uri, '/'), array_replace_recursive([
                'http_errors' => false,
                'timeout' => (float) ($server['timeout'] ?? 30),
                'verify' => (bool) ($server['verify'] ?? true),
            ], $options));
        } catch (GuzzleException $exception) {
            throw new RuntimeException('Control-panel API request failed.', 0, $exception);
        }

        $status = $response->getStatusCode();
        $body = (string) $response->getBody();
        if ($status < 200 || $status >= 300) {
            throw new RuntimeException("Control-panel API returned HTTP {$status}.");
        }
        parse_str($body, $parsed);

        return array_map(static fn (mixed $value): string => (string) $value, $parsed);
    }

    /** @param array<string, mixed> $server */
    protected function requestXml(string $method, string $uri, array $server, string $body, array $headers = []): string
    {
        try {
            $response = $this->client->request($method, $this->baseUrl($server).'/'.ltrim($uri, '/'), [
                'body' => $body,
                'headers' => array_merge(['Accept' => 'application/xml', 'Content-Type' => 'text/xml'], $headers),
                'http_errors' => false,
                'timeout' => (float) ($server['timeout'] ?? 30),
                'verify' => (bool) ($server['verify'] ?? true),
            ]);
        } catch (GuzzleException $exception) {
            throw new RuntimeException('Control-panel API request failed.', 0, $exception);
        }

        $status = $response->getStatusCode();
        if ($status < 200 || $status >= 300) {
            throw new RuntimeException("Control-panel API returned HTTP {$status}.");
        }

        return (string) $response->getBody();
    }

    /** @param array<string, mixed> $server */
    protected function assertSuccessful(array $response): void
    {
        if (($response['status'] ?? null) === 'error' || ($response['success'] ?? true) === false) {
            throw new RuntimeException((string) ($response['message'] ?? $response['error'] ?? 'Control-panel operation was rejected.'));
        }
    }
}
