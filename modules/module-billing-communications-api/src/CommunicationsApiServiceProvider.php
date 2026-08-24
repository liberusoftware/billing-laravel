<?php

declare(strict_types=1);

namespace Liberu\Billing\Communications\Api;

use Illuminate\Support\ServiceProvider;

final class CommunicationsApiServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->loadRoutesFrom(__DIR__.'/../routes/api.php');
        $this->publishes([__DIR__.'/../openapi' => base_path('docs/api/billing-communications')], 'billing-communications-openapi');
    }
}
