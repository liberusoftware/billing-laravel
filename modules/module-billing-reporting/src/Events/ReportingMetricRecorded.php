<?php

declare(strict_types=1);

namespace Liberu\Billing\Reporting\Events;

use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Liberu\Billing\Reporting\Models\ReportingMetric;

final class ReportingMetricRecorded implements ShouldDispatchAfterCommit
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(public readonly ReportingMetric $metric) {}
}
