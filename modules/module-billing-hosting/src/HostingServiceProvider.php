<?php

declare(strict_types=1);

namespace Liberu\Billing\Hosting;

use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Liberu\Billing\Hosting\Models\HostingAccount;
use Liberu\Billing\Hosting\Models\HostingCapability;
use Liberu\Billing\Hosting\Policies\HostingAccountPolicy;
use Liberu\Billing\Hosting\Policies\HostingCapabilityPolicy;
use Liberu\Billing\Hosting\Services\HostingDriverRegistry;

final class HostingServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(HostingDriverRegistry::class);
    }

    public function boot(): void
    {
        Gate::policy(HostingAccount::class, HostingAccountPolicy::class);
        Gate::policy(HostingCapability::class, HostingCapabilityPolicy::class);
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
    }
}
