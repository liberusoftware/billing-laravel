<?php

declare(strict_types=1);

namespace Liberu\Billing\CustomerPortal\Livewire;

use Illuminate\Support\ServiceProvider;
use Liberu\Billing\CustomerPortal\Livewire\Components\PortalItems;
use Livewire\Livewire;

final class CustomerPortalLivewireServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'billing-customer-portal-livewire');
        Livewire::component('module-billing-customer-portal::items', PortalItems::class);
    }
}
