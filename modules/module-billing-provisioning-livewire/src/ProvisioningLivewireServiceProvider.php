<?php

declare(strict_types=1);

namespace Liberu\Billing\Provisioning\Livewire;

use Illuminate\Support\ServiceProvider;
use Liberu\Billing\Provisioning\Livewire\Components\ProvisioningOperations;
use Livewire\Livewire;

final class ProvisioningLivewireServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'module-billing-provisioning-livewire');
        Livewire::component('module-billing-provisioning::operations', ProvisioningOperations::class);
    }
}
