<?php

declare(strict_types=1);

namespace Liberu\Billing\Domains\Services;

use InvalidArgumentException;
use Liberu\Billing\Domains\Contracts\RegistrarClient;

final class RegistrarManager
{
    /** @var array<string, RegistrarClient> */
    private array $clients = [];

    public function register(string $name, RegistrarClient $client): void
    {
        $this->clients[strtolower($name)] = $client;
    }

    public function client(string $name): RegistrarClient
    {
        $client = $this->clients[strtolower($name)] ?? null;
        if (! $client instanceof RegistrarClient) {
            throw new InvalidArgumentException("Registrar [$name] is not registered.");
        }

        return $client;
    }
}
