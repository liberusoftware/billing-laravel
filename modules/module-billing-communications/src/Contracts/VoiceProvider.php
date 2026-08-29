<?php

declare(strict_types=1);

namespace Liberu\Billing\Communications\Contracts;

interface VoiceProvider
{
    public function key(): string;

    /** @param array<string,mixed> $attributes @return array<string,mixed> */
    public function provision(array $attributes): array;
}
