<?php

declare(strict_types=1);

namespace Liberu\Billing\Invoicing;

use Illuminate\Support\ServiceProvider;

class InvoicingServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
    }
}
