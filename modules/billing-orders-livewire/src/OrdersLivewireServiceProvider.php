<?php

declare(strict_types=1);

namespace Liberu\Billing\Orders\Livewire;

use Illuminate\Support\ServiceProvider;
use Liberu\Billing\Orders\Livewire\Components\OrderList;
use Liberu\Billing\Orders\Livewire\Components\OrderSupport;
use Liberu\Billing\Orders\Livewire\Components\QuoteList;
use Livewire\Livewire;

final class OrdersLivewireServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'billing-orders-livewire');
        Livewire::component('module-billing-orders::order-list', OrderList::class);
        Livewire::component('module-billing-orders::order-support', OrderSupport::class);
        Livewire::component('module-billing-orders::quote-list', QuoteList::class);
    }
}
