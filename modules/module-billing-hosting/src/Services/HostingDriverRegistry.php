<?php

declare(strict_types=1);

namespace Liberu\Billing\Hosting\Services;

use InvalidArgumentException;
use Liberu\Billing\Hosting\Contracts\HostingDriver;

final class HostingDriverRegistry
{
    /** @var array<string, HostingDriver> */
    private array $drivers = [];

    public function register(HostingDriver $driver): void
    {
        $key = trim($driver->key());
        if ($key === '' || isset($this->drivers[$key])) {
            throw new InvalidArgumentException('Hosting driver keys must be non-empty and unique.');
        }

        $this->drivers[$key] = $driver;
    }

    public function resolve(string $key): HostingDriver
    {
        return $this->drivers[$key] ?? throw new InvalidArgumentException("Hosting driver [{$key}] is not registered.");
    }

    /** @return list<string> */
    public function keys(): array
    {
        return array_keys($this->drivers);
    }
}
