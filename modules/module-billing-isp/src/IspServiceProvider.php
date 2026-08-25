<?php

declare(strict_types=1);

namespace Liberu\Billing\Isp;

use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Liberu\Billing\Isp\Models\AccessService;
use Liberu\Billing\Isp\Models\IspCapability;
use Liberu\Billing\Isp\Policies\AccessServicePolicy;
use Liberu\Billing\Isp\Policies\IspCapabilityPolicy;
use Liberu\Billing\Isp\Services\NetworkAdapterRegistry;

final class IspServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(NetworkAdapterRegistry::class);
    }

    public function boot(): void
    {
        Gate::policy(AccessService::class, AccessServicePolicy::class);
        Gate::policy(IspCapability::class, IspCapabilityPolicy::class);
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
    }
}
