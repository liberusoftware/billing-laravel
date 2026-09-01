<?php

declare(strict_types=1);

namespace Liberu\Billing\Core\Events;

use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Foundation\Events\Dispatchable;

final class BillingRecordDeleted implements ShouldDispatchAfterCommit
{
    use Dispatchable;

    public function __construct(public readonly string $recordType, public readonly int|string $recordId, public readonly int $teamId) {}
}
