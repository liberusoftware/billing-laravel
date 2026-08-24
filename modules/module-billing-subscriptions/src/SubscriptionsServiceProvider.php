<?php

declare(strict_types=1);

namespace Liberu\Billing\Subscriptions;

use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Liberu\Billing\Subscriptions\Models\Subscription;
use Liberu\Billing\Subscriptions\Policies\SubscriptionPolicy;

final class SubscriptionsServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
        Gate::policy(Subscription::class, SubscriptionPolicy::class);
    }
}
