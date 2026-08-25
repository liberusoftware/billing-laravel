<?php

declare(strict_types=1);

namespace Liberu\Billing\Catalog;

use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Liberu\Billing\Catalog\Models\Addon;
use Liberu\Billing\Catalog\Models\Bundle;
use Liberu\Billing\Catalog\Models\Channel;
use Liberu\Billing\Catalog\Models\ConfigurableOption;
use Liberu\Billing\Catalog\Models\Eligibility;
use Liberu\Billing\Catalog\Models\Plan;
use Liberu\Billing\Catalog\Models\Product;
use Liberu\Billing\Catalog\Policies\CatalogRecordPolicy;
use Liberu\Billing\Catalog\Policies\ProductPolicy;

final class CatalogServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
        Gate::policy(Product::class, ProductPolicy::class);
        foreach ([Plan::class, Addon::class, Bundle::class, ConfigurableOption::class, Eligibility::class, Channel::class] as $model) {
            Gate::policy($model, CatalogRecordPolicy::class);
        }
    }
}
