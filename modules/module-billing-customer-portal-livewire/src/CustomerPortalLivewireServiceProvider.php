<?php

declare(strict_types=1);

namespace Liberu\Billing\CustomerPortal\Livewire;

use Illuminate\Support\ServiceProvider;
use Liberu\Billing\CustomerPortal\Livewire\Components\PortalBilling;
use Liberu\Billing\CustomerPortal\Livewire\Components\PortalDashboard;
use Liberu\Billing\CustomerPortal\Livewire\Components\PortalItems;
use Liberu\Billing\CustomerPortal\Livewire\Components\PortalRequests;
use Livewire\Livewire;

final class CustomerPortalLivewireServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'billing-customer-portal-livewire');
        Livewire::component('module-billing-customer-portal::items', PortalItems::class);
        Livewire::component('module-billing-customer-portal::dashboard', PortalDashboard::class);
        Livewire::component('module-billing-customer-portal::requests', PortalRequests::class);
        Livewire::component('module-billing-customer-portal::billing', PortalBilling::class);
    }
}
