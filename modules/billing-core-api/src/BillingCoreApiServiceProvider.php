<?php

declare(strict_types=1);

namespace Liberu\Billing\Core\Api;

use Illuminate\Support\ServiceProvider;

final class BillingCoreApiServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->loadRoutesFrom(__DIR__.'/../routes/api.php');
    }
}
