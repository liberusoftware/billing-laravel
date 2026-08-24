<?php

declare(strict_types=1);

namespace Liberu\Billing\Isp;

use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Liberu\Billing\Isp\Models\AccessService;
use Liberu\Billing\Isp\Policies\AccessServicePolicy;

final class IspServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        Gate::policy(AccessService::class, AccessServicePolicy::class);
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
    }
}
