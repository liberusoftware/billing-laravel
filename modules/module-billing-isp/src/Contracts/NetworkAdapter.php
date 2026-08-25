<?php

declare(strict_types=1);

namespace Liberu\Billing\Isp\Contracts;

interface NetworkAdapter
{
    public function key(): string;

    /** @param array<string,mixed> $attributes @return array<string,mixed> */
    public function install(array $attributes): array;

    /** @param array<string,mixed> $attributes @return array<string,mixed> */
    public function suspend(array $attributes): array;

    /** @param array<string,mixed> $attributes @return array<string,mixed> */
    public function remove(array $attributes): array;
}
