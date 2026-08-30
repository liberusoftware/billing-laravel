<?php

declare(strict_types=1);

namespace Liberu\Billing\Reporting\Events;

use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Liberu\Billing\Reporting\Models\MetricSnapshot;

final class MetricSnapshotCreated implements ShouldDispatchAfterCommit
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(public readonly MetricSnapshot $snapshot) {}
}
