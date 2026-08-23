<?php

declare(strict_types=1);

namespace App\Services\Radius;

use App\Contracts\RadiusClient;
use App\Enums\RadiusPlatform;
use App\Models\IspService;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use RuntimeException;
use Throwable;

class HttpRadiusClient implements RadiusClient
{
    public function __construct(private readonly RadiusPlatform $platform) {}

    public function synchronizeUser(IspService $service): void
    {
        $this->send('put', '/users/'.rawurlencode($service->radius_username), [
            'username' => $service->radius_username,
            'secret' => $service->radius_secret,
            'enabled' => true,
            'download_limit_bps' => $service->download_limit_bps,
            'upload_limit_bps' => $service->upload_limit_bps,
            'data_limit_bytes' => $service->monthly_data_limit_bytes,
        ]);
    }

    public function suspendUser(IspService $service): void
    {
        $this->send('patch', '/users/'.rawurlencode($service->radius_username), [
            'enabled' => false,
        ]);
    }

    public function disconnectUser(IspService $service): void
    {
        $this->send('post', '/sessions/disconnect', [
            'username' => $service->radius_username,
        ]);
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function send(string $method, string $path, array $payload): void
    {
        try {
            $this->request()->send($method, $path, ['json' => $payload])->throw();
        } catch (Throwable $exception) {
            report($exception);

            throw new RuntimeException("{$this->platform->value} RADIUS operation failed.");
        }
    }

    private function request(): PendingRequest
    {
        $url = config("radius.platforms.{$this->platform->value}.url");
        $token = config("radius.platforms.{$this->platform->value}.token");

        if (! is_string($url) || $url === '' || ! is_string($token) || $token === '') {
            throw new RuntimeException("{$this->platform->value} RADIUS integration is not configured.");
        }

        return Http::baseUrl(rtrim($url, '/'))
            ->withToken($token)
            ->acceptJson()
            ->asJson()
            ->timeout((int) config('radius.timeout', 10))
            ->retry(2, 200);
    }
}
