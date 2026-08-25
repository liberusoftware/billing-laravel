<?php

declare(strict_types=1);

namespace Liberu\Billing\Hosting\Contracts;

interface HostingDriver
{
    public function key(): string;

    /** @param array<string,mixed> $attributes @return array<string,mixed> */
    public function provision(array $attributes): array;

    /** @param array<string,mixed> $attributes @return array<string,mixed> */
    public function suspend(array $attributes): array;

    /** @param array<string,mixed> $attributes @return array<string,mixed> */
    public function terminate(array $attributes): array;
}
