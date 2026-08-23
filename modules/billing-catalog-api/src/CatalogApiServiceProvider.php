<?php

declare(strict_types=1);

namespace Liberu\Billing\Catalog\Api;

use Illuminate\Support\ServiceProvider;

final class CatalogApiServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->loadRoutesFrom(__DIR__.'/../routes/api.php');
    }
}
