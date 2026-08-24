<?php

declare(strict_types=1);

namespace Liberu\Billing\Reporting\Api;

use Illuminate\Support\ServiceProvider;

final class ReportingApiServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->loadRoutesFrom(__DIR__.'/../routes/api.php');
        $this->publishes([__DIR__.'/../openapi' => base_path('docs/api/billing-reporting')], 'billing-reporting-openapi');
    }
}
