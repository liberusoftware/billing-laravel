<?php

declare(strict_types=1);

namespace Liberu\Billing\Core;

use Illuminate\Support\ServiceProvider;

final class BillingCoreServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->loadTranslationsFrom(__DIR__.'/../lang', 'billing-core');
    }
}
