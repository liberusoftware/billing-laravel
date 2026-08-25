<?php

declare(strict_types=1);

namespace Liberu\Billing\Stripe;

use Illuminate\Support\ServiceProvider;
use Liberu\Billing\Payments\Services\GatewayManager;
use Stripe\StripeClient;

final class StripeServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(StripeClient::class, fn (): StripeClient => new StripeClient((string) config('services.stripe.secret')));
    }

    public function boot(GatewayManager $gateways): void
    {
        $gateways->register('stripe', $this->app->make(StripeGatewayDriver::class));
        $gateways->register('Stripe', $this->app->make(StripeGatewayDriver::class));
    }
}
