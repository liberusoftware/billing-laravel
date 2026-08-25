<?php

declare(strict_types=1);

namespace Liberu\Billing\Usage;

use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Liberu\Billing\Usage\Models\Meter;
use Liberu\Billing\Usage\Models\UsageRecord;
use Liberu\Billing\Usage\Policies\UsagePolicy;
use Liberu\Billing\Usage\Policies\UsageRecordPolicy;

final class UsageServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
        Gate::policy(Meter::class, UsagePolicy::class);
        Gate::policy(UsageRecord::class, UsageRecordPolicy::class);
    }
}
