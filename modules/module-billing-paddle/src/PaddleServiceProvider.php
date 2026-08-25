<?php

declare(strict_types=1);

namespace Liberu\Billing\Paddle;

use Illuminate\Support\ServiceProvider;
use Liberu\Billing\Payments\Services\GatewayManager;

final class PaddleServiceProvider extends ServiceProvider
{
    public function boot(GatewayManager $gateways): void
    {
        $gateways->register('paddle', $this->app->make(PaddleGatewayDriver::class));
        $gateways->register('Paddle', $this->app->make(PaddleGatewayDriver::class));
    }
}
