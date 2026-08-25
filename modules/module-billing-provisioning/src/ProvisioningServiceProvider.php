<?php

declare(strict_types=1);

namespace Liberu\Billing\Provisioning;

use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Liberu\Billing\Provisioning\Models\ProvisionedService;
use Liberu\Billing\Provisioning\Models\ProvisioningOperation;
use Liberu\Billing\Provisioning\Policies\ProvisioningPolicy;
use Liberu\Billing\Provisioning\Services\ProvisioningDriverRegistry;

final class ProvisioningServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(ProvisioningDriverRegistry::class);
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
        Gate::policy(ProvisionedService::class, ProvisioningPolicy::class);
        Gate::policy(ProvisioningOperation::class, ProvisioningPolicy::class);
    }
}
