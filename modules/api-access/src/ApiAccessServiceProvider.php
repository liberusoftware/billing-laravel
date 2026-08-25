<?php

namespace Liberu\Foundation\ApiAccess;

use Illuminate\Routing\Router;
use Illuminate\Support\ServiceProvider;
use Liberu\Foundation\ApiAccess\Http\Middleware\ReplayIdempotentRequest;

final class ApiAccessServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/api-access.php', 'api-access');
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
        $this->app->make(Router::class)->aliasMiddleware('idempotency', ReplayIdempotentRequest::class);
    }
}
