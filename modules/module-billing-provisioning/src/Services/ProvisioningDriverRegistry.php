<?php

declare(strict_types=1);

namespace Liberu\Billing\Provisioning\Services;

use InvalidArgumentException;
use Liberu\Billing\Provisioning\Contracts\ProvisioningDriver;

final class ProvisioningDriverRegistry
{
    /** @var array<string, ProvisioningDriver> */
    private array $drivers = [];

    public function register(string $provider, ProvisioningDriver $driver): void
    {
        $provider = trim($provider);
        if ($provider === '' || isset($this->drivers[$provider])) {
            throw new InvalidArgumentException('Provisioning driver providers must be non-empty and unique.');
        }

        $this->drivers[$provider] = $driver;
    }

    public function resolve(string $provider): ProvisioningDriver
    {
        return $this->drivers[$provider] ?? throw new InvalidArgumentException("Provisioning driver [{$provider}] is not registered.");
    }
}
