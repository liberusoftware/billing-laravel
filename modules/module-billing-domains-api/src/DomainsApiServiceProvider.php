<?php

declare(strict_types=1);

namespace Liberu\Billing\Domains\Api;

use Illuminate\Support\ServiceProvider;

final class DomainsApiServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->loadRoutesFrom(__DIR__.'/../routes/api.php');
        $this->publishes([__DIR__.'/../openapi' => base_path('docs/api/billing-domains')], 'billing-domains-openapi');
    }
}
