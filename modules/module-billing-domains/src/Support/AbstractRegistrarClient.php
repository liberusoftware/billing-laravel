<?php

declare(strict_types=1);

namespace Liberu\Billing\Domains\Support;

use Illuminate\Http\Client\Factory;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Carbon;
use InvalidArgumentException;
use RuntimeException;

abstract class AbstractRegistrarClient
{
    public function __construct(protected Factory $http) {}

    /** @return array{0:string,1:string} */
    protected function domainParts(string $domain): array
    {
        $domain = strtolower(rtrim(trim($domain), '.'));
        $position = strrpos($domain, '.');
        if ($position === false || $position === 0 || $position === strlen($domain) - 1) {
            throw new InvalidArgumentException('A fully qualified domain name is required.');
        }

        return [substr($domain, 0, $position), ltrim(substr($domain, $position), '.')];
    }

    /** @param array<string, mixed> $query @return array<string, mixed> */
    protected function get(string $url, array $query, array $headers = []): array
    {
        $response = $this->http->withHeaders($headers)->connectTimeout(5)->timeout(30)->get($url, $query);

        return $this->response($response);
    }

    /** @param array<string, mixed> $payload @return array<string, mixed> */
    protected function post(string $url, array $payload, array $headers = []): array
    {
        $response = $this->http->withHeaders($headers)->connectTimeout(5)->timeout(30)->post($url, $payload);

        return $this->response($response);
    }

    /** @return array<string, mixed> */
    protected function response(Response $response): array
    {
        if ($response->failed()) {
            throw new RuntimeException('Registrar API returned HTTP '.$response->status().'.');
        }
        $json = $response->json();
        if (is_array($json)) {
            return $json;
        }
        $body = trim($response->body());
        if (str_starts_with($body, '<')) {
            $previous = libxml_use_internal_errors(true);
            try {
                // Do not resolve entities or load external documents from a provider response.
                if (stripos($body, '<!DOCTYPE') !== false || stripos($body, '<!ENTITY') !== false) {
                    throw new RuntimeException('Registrar API returned an invalid response.');
                }
                $xml = simplexml_load_string($body, \SimpleXMLElement::class, LIBXML_NONET | LIBXML_NOCDATA);
                if ($xml === false || $xml->getName() !== 'interface-response') {
                    throw new RuntimeException('Registrar API returned an invalid response.');
                }

                return json_decode(json_encode($xml, JSON_THROW_ON_ERROR), true, 512, JSON_THROW_ON_ERROR);
            } finally {
                libxml_clear_errors();
                libxml_use_internal_errors($previous);
            }
        }

        if ($body === '' || ! str_contains($body, '=')) {
            throw new RuntimeException('Registrar API returned an invalid response.');
        }
        parse_str($body, $parsed);

        return $parsed;
    }

    protected function configValue(string $key, ?string $default = null): string
    {
        $value = config($key, $default);
        if (! is_string($value) || trim($value) === '') {
            throw new InvalidArgumentException('Registrar credentials are not configured.');
        }

        return trim($value);
    }

    protected function dateValue(mixed $value): ?Carbon
    {
        if ($value === null || $value === '') {
            return null;
        }

        try {
            return Carbon::parse((string) $value);
        } catch (\Throwable) {
            return null;
        }
    }
}
