<?php

declare(strict_types=1);

namespace Liberu\Billing\Reporting;

use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Liberu\Billing\Reporting\Models\MetricSnapshot;
use Liberu\Billing\Reporting\Models\ReportingMetric;
use Liberu\Billing\Reporting\Policies\MetricSnapshotPolicy;
use Liberu\Billing\Reporting\Policies\ReportingMetricPolicy;

final class ReportingServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        Gate::policy(MetricSnapshot::class, MetricSnapshotPolicy::class);
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
        Gate::policy(ReportingMetric::class, ReportingMetricPolicy::class);
    }
}
