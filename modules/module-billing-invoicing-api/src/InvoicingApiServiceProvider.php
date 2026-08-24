<?php

declare(strict_types=1);

namespace Liberu\Billing\Invoicing\Api;

use Illuminate\Support\ServiceProvider;

final class InvoicingApiServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->loadRoutesFrom(__DIR__.'/../routes/api.php');
    }
}
