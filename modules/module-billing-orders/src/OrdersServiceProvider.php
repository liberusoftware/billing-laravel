<?php

declare(strict_types=1);

namespace Liberu\Billing\Orders;

use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Liberu\Billing\Orders\Models\Order;
use Liberu\Billing\Orders\Models\Quote;
use Liberu\Billing\Orders\Policies\OrderPolicy;
use Liberu\Billing\Orders\Policies\QuotePolicy;

final class OrdersServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
        Gate::policy(Order::class, OrderPolicy::class);
        Gate::policy(Quote::class, QuotePolicy::class);
    }
}
