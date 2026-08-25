<?php

declare(strict_types=1);

namespace Liberu\Billing\Communications\Contracts;

interface CommunicationProvider
{
    /** @return array{number: string, type: string, status: string} */
    public function provisionNumber(string $number, string $type = 'phone'): array;

    /** @param iterable<array<string,mixed>> $rows */
    public function importUsage(iterable $rows): int;
}
