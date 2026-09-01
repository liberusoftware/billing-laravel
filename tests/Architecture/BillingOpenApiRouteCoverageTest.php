<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Symfony\Component\Yaml\Yaml;

it('documents every registered billing API route in an owned OpenAPI fragment', function (): void {
    $documented = [];

    foreach (glob(base_path('modules/*-api/openapi/v1/*.yaml')) ?: [] as $path) {
        $document = Yaml::parseFile($path);
        foreach ($document['paths'] ?? [] as $uri => $operations) {
            foreach (array_keys($operations) as $method) {
                if (in_array($method, ['parameters', 'summary', 'description'], true)) {
                    continue;
                }

                $documented[strtoupper($method).' '.$uri] = true;
            }
        }
    }

    $missing = [];
    foreach (Route::getRoutes() as $route) {
        $uri = '/'.ltrim($route->uri(), '/');
        if (! str_starts_with($uri, '/api/v1/billing/')) {
            continue;
        }

        $normalized = preg_replace('/\{[^}]+\}/', '{parameter}', $uri);
        foreach ($route->methods() as $method) {
            if ($method === 'HEAD') {
                continue;
            }

            $documentedUri = preg_replace('/\{[^}]+\}/', '{parameter}', $normalized ?? $uri);
            $hasDocumentedPath = false;
            foreach ($documented as $key => $_) {
                [$documentedMethod, $path] = explode(' ', $key, 2);
                if ($documentedMethod === $method && preg_replace('/\{[^}]+\}/', '{parameter}', $path) === $documentedUri) {
                    $hasDocumentedPath = true;
                    break;
                }
            }

            if (! $hasDocumentedPath) {
                $missing[] = $method.' '.$uri;
            }
        }
    }

    expect($missing)->toBe([]);
});
