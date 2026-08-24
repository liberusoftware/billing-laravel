<?php

declare(strict_types=1);

namespace Liberu\Billing\Payments;

use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Liberu\Billing\Payments\Models\Payment;
use Liberu\Billing\Payments\Policies\PaymentPolicy;
use Liberu\Billing\Payments\Services\GatewayManager;

final class PaymentsServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(GatewayManager::class);
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
        Gate::policy(Payment::class, PaymentPolicy::class);
    }
}
