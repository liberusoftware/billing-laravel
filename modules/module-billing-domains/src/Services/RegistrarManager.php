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
        $name = strtolower(trim($name));
        if ($name === '') {
            throw new InvalidArgumentException('Registrar name cannot be empty.');
        }

        $this->clients[$name] = $client;
    }

    public function client(string $name): RegistrarClient
    {
        $client = $this->clients[strtolower(trim($name))] ?? null;
        if (! $client instanceof RegistrarClient) {
            throw new InvalidArgumentException("Registrar [$name] is not registered.");
        }

        return $client;
    }
}
