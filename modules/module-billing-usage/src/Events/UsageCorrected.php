<?php

declare(strict_types=1);

namespace Liberu\Billing\Usage\Events;

use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Liberu\Billing\Usage\Models\UsageRecord;

final class UsageCorrected implements ShouldDispatchAfterCommit
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(public readonly UsageRecord $record) {}
}
