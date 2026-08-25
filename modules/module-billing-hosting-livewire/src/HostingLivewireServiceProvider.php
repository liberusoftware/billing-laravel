<?php

declare(strict_types=1);

namespace Liberu\Billing\Hosting\Livewire;

use Illuminate\Support\ServiceProvider;
use Liberu\Billing\Hosting\Livewire\Components\HostingAccounts;
use Liberu\Billing\Hosting\Livewire\Components\HostingCapabilities;
use Livewire\Livewire;

final class HostingLivewireServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'module-billing-hosting-livewire');
        Livewire::component('module-billing-hosting::accounts', HostingAccounts::class);
        Livewire::component('module-billing-hosting::capabilities', HostingCapabilities::class);
    }
}
