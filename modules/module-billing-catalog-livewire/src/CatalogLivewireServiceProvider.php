<?php

declare(strict_types=1);

namespace Liberu\Billing\Catalog\Livewire;

use Illuminate\Support\ServiceProvider;
use Liberu\Billing\Catalog\Livewire\Components\CatalogRecords;
use Liberu\Billing\Catalog\Livewire\Components\ProductCatalog;
use Livewire\Livewire;

final class CatalogLivewireServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'module-billing-catalog-livewire');
        Livewire::component('module-billing-catalog::product-catalog', ProductCatalog::class);
        Livewire::component('module-billing-catalog::records', CatalogRecords::class);
    }
}
