<?php

declare(strict_types=1);

namespace Liberu\Billing\Domains\Events;

use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Foundation\Events\Dispatchable;

final class DomainDeleted implements ShouldDispatchAfterCommit
{
    use Dispatchable;

    public function __construct(public readonly int|string $domainId, public readonly int $teamId) {}
}
