<?php

declare(strict_types=1);

namespace Liberu\Billing\Pricing\Api;

use Illuminate\Support\ServiceProvider;

final class PricingApiServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->loadRoutesFrom(__DIR__.'/../routes/api.php');
    }
}
