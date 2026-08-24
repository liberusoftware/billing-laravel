<?php

declare(strict_types=1);

namespace Liberu\Billing\Usage;

use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Liberu\Billing\Usage\Models\Meter;
use Liberu\Billing\Usage\Policies\UsagePolicy;

final class UsageServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
        Gate::policy(Meter::class, UsagePolicy::class);
    }
}
