<?php

declare(strict_types=1);

namespace Liberu\Foundation\ApiAccess\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Liberu\Foundation\ApiAccess\Support\IdempotencyStore;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

final class ReplayIdempotentRequest
{
    public function __construct(private readonly IdempotencyStore $store) {}

    public function handle(Request $request, Closure $next): SymfonyResponse
    {
        $key = trim((string) $request->header('Idempotency-Key', ''));

        if ($key === '' || in_array($request->method(), ['GET', 'HEAD', 'OPTIONS'], true)) {
            return $next($request);
        }

        $identity = sprintf('%s:%s', (string) ($request->user()?->getAuthIdentifier() ?? 'guest'), (string) ($request->user()?->current_team_id ?? 'none'));
        $existing = $this->store->begin($identity, $key, $request->getContent());

        if ($existing !== null && $existing->response_body !== null) {
            return new Response((string) $existing->response_body, (int) $existing->response_status, [
                'Content-Type' => 'application/json',
                'Idempotent-Replayed' => 'true',
            ]);
        }

        $response = $next($request);
        $body = $response->getContent();

        if ($body !== false) {
            $this->store->complete($identity, $key, $response->getStatusCode(), $body);
        }

        return $response;
    }
}
