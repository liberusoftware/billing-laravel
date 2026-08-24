<?php

declare(strict_types=1);

namespace Liberu\Billing\Invoicing;

use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Liberu\Billing\Invoicing\Models\Invoice;
use Liberu\Billing\Invoicing\Policies\InvoicePolicy;

class InvoicingServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
        Gate::policy(Invoice::class, InvoicePolicy::class);
    }
}
