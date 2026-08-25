<?php

declare(strict_types=1);

namespace Liberu\Billing\Isp\Services;

use InvalidArgumentException;
use Liberu\Billing\Isp\Contracts\NetworkAdapter;

final class NetworkAdapterRegistry
{
    /** @var array<string, NetworkAdapter> */
    private array $adapters = [];

    public function register(NetworkAdapter $adapter): void
    {
        $key = trim($adapter->key());
        if ($key === '' || isset($this->adapters[$key])) {
            throw new InvalidArgumentException('Network adapter keys must be non-empty and unique.');
        }

        $this->adapters[$key] = $adapter;
    }

    public function resolve(string $key): NetworkAdapter
    {
        return $this->adapters[$key] ?? throw new InvalidArgumentException("Network adapter [{$key}] is not registered.");
    }

    /** @return list<string> */
    public function keys(): array
    {
        return array_keys($this->adapters);
    }
}
