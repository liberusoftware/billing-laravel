<?php

declare(strict_types=1);

namespace Liberu\Billing\CustomerPortal;

use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Liberu\Billing\CustomerPortal\Models\PortalItem;
use Liberu\Billing\CustomerPortal\Models\PortalRequest;
use Liberu\Billing\CustomerPortal\Policies\PortalItemPolicy;
use Liberu\Billing\CustomerPortal\Policies\PortalRequestPolicy;

final class CustomerPortalServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        Gate::policy(PortalRequest::class, PortalRequestPolicy::class);
        Gate::policy(PortalItem::class, PortalItemPolicy::class);
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
    }
}
