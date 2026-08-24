<?php

declare(strict_types=1);

namespace Liberu\Billing\Reporting;

use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Liberu\Billing\Reporting\Models\MetricSnapshot;
use Liberu\Billing\Reporting\Policies\MetricSnapshotPolicy;

final class ReportingServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        Gate::policy(MetricSnapshot::class, MetricSnapshotPolicy::class);
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
    }
}
