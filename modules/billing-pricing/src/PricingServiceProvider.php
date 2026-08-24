<?php

declare(strict_types=1);

namespace Liberu\Billing\Pricing;

use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Liberu\Billing\Pricing\Models\PricingPlan;
use Liberu\Billing\Pricing\Policies\PricingPlanPolicy;

final class PricingServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
        Gate::policy(PricingPlan::class, PricingPlanPolicy::class);
    }
}
