<?php

declare(strict_types=1);

namespace Liberu\Billing\Core;

use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Liberu\Billing\Core\Models\BillingAccount;
use Liberu\Billing\Core\Policies\BillingAccountPolicy;

final class BillingCoreServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
        $this->loadTranslationsFrom(__DIR__.'/../lang', 'billing-core');
        Gate::policy(BillingAccount::class, BillingAccountPolicy::class);
    }
}
