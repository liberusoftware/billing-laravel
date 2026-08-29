<?php

declare(strict_types=1);

namespace Liberu\Billing\Payments\Services;

use Liberu\Billing\Payments\Contracts\GatewayDriver;

final class GatewayManager
{
    /** @var array<string, GatewayDriver> */
    private array $drivers = [];

    public function register(string $name, GatewayDriver $driver): void
    {
        $name = strtolower(trim($name));
        if ($name === '') {
            throw new \InvalidArgumentException('Payment gateway name cannot be empty.');
        }

        $this->drivers[$name] = $driver;
    }

    public function driver(string $name): GatewayDriver
    {
        $driver = $this->drivers[strtolower(trim($name))] ?? null;
        if (! $driver instanceof GatewayDriver) {
            throw new \InvalidArgumentException("Payment gateway [$name] is not registered.");
        }

        return $driver;
    }
}
