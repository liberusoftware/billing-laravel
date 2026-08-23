<?php

declare(strict_types=1);

namespace App\Services\Voip;

use App\Contracts\VoipPlatformClient;
use App\Enums\VoipPlatform;
use App\Models\VoipAccount;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use RuntimeException;
use Throwable;

class HttpVoipPlatformClient implements VoipPlatformClient
{
    public function __construct(private readonly VoipPlatform $platform) {}

    public function provisionAccount(VoipAccount $account): void
    {
        $this->send('post', '/sip/accounts', $this->payload($account));
    }

    public function synchronizeAccount(VoipAccount $account): void
    {
        $this->send('put', '/sip/accounts/'.rawurlencode($account->sip_username), $this->payload($account));
    }

    public function suspendAccount(VoipAccount $account): void
    {
        $this->send('patch', '/sip/accounts/'.rawurlencode($account->sip_username), ['enabled' => false]);
    }

    /**
     * @return array<string, mixed>
     */
    private function payload(VoipAccount $account): array
    {
        return [
            'username' => $account->sip_username,
            'secret' => $account->sip_secret,
            'caller_id' => $account->caller_id,
            'max_concurrent_calls' => $account->max_concurrent_calls,
            'international_enabled' => $account->international_enabled,
            'enabled' => true,
            'did_numbers' => $account->didNumbers()->pluck('number')->all(),
        ];
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

            throw new RuntimeException("{$this->platform->value} VoIP operation failed.");
        }
    }

    private function request(): PendingRequest
    {
        $url = config("voip.platforms.{$this->platform->value}.url");
        $token = config("voip.platforms.{$this->platform->value}.token");

        if (! is_string($url) || $url === '' || ! is_string($token) || $token === '') {
            throw new RuntimeException("{$this->platform->value} VoIP integration is not configured.");
        }

        return Http::baseUrl(rtrim($url, '/'))
            ->withToken($token)
            ->acceptJson()
            ->asJson()
            ->timeout((int) config('voip.timeout', 10))
            ->retry(2, 200);
    }
}
