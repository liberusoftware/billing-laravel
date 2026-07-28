<?php

declare(strict_types=1);

namespace App\Contracts;

use App\Models\DomainEventMessage;
use App\Models\ExternalConnection;

interface BusinessConnector
{
    /** @param array<string, mixed> $payload */
    public function push(
        ExternalConnection $connection,
        string $resource,
        string $localId,
        array $payload,
        ?string $remoteId = null
    ): string;

    /** @return array{items: list<array<string, mixed>>, cursor: string|null} */
    public function pull(ExternalConnection $connection, string $resource, ?string $cursor = null): array;

    public function publish(ExternalConnection $connection, DomainEventMessage $event): void;
}
