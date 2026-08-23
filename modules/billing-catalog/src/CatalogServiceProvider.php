<?php

declare(strict_types=1);

namespace Liberu\Billing\Catalog;

use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Liberu\Billing\Catalog\Models\Product;
use Liberu\Billing\Catalog\Policies\ProductPolicy;

final class CatalogServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
        Gate::policy(Product::class, ProductPolicy::class);
    }
}
