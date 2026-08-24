<?php

declare(strict_types=1);

namespace Liberu\Billing\Subscriptions\Api;

use Illuminate\Support\ServiceProvider;

final class SubscriptionsApiServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->loadRoutesFrom(__DIR__.'/../routes/api.php');
    }
}
