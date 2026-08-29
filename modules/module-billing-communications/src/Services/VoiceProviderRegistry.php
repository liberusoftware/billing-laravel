<?php

declare(strict_types=1);

namespace Liberu\Billing\Communications\Services;

use InvalidArgumentException;
use Liberu\Billing\Communications\Contracts\VoiceProvider;

final class VoiceProviderRegistry
{
    /** @var array<string,VoiceProvider> */
    private array $providers = [];

    public function register(VoiceProvider $provider): void
    {
        $key = strtolower(trim($provider->key()));
        if ($key === '' || isset($this->providers[$key])) {
            throw new InvalidArgumentException('Voice provider keys must be non-empty and unique.');
        }

        $this->providers[$key] = $provider;
    }

    public function resolve(string $key): VoiceProvider
    {
        $key = strtolower(trim($key));

        return $this->providers[$key] ?? throw new InvalidArgumentException("Voice provider [{$key}] is not registered.");
    }
}
