<?php

declare(strict_types=1);

namespace Liberu\Billing\Core\Events;

use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Foundation\Events\Dispatchable;

final class BillingAccountDeleted implements ShouldDispatchAfterCommit
{
    use Dispatchable;

    public function __construct(public readonly int|string $accountId, public readonly ?int $teamId) {}
}
