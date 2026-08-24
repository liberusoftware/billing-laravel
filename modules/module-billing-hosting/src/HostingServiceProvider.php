<?php

declare(strict_types=1);

namespace Liberu\Billing\Hosting;

use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Liberu\Billing\Hosting\Models\HostingAccount;
use Liberu\Billing\Hosting\Policies\HostingAccountPolicy;

final class HostingServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        Gate::policy(HostingAccount::class, HostingAccountPolicy::class);
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
    }
}
