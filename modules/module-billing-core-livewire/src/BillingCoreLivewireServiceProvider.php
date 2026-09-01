<?php

declare(strict_types=1);

namespace Liberu\Billing\Core\Livewire;

use Illuminate\Support\ServiceProvider;
use Liberu\Billing\Core\Livewire\Components\BillingAccounts;
use Liberu\Billing\Core\Livewire\Components\BillingCoreRecords;
use Liberu\Billing\Core\Livewire\Components\CurrencyConverter;
use Liberu\Billing\Core\Livewire\Components\TaxCalculator;
use Livewire\Livewire;

final class BillingCoreLivewireServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'module-billing-core-livewire');
        Livewire::component('module-billing-core::billing-accounts', BillingAccounts::class);
        Livewire::component('module-billing-core::records', BillingCoreRecords::class);
        Livewire::component('module-billing-core::currency-converter', CurrencyConverter::class);
        Livewire::component('module-billing-core::tax-calculator', TaxCalculator::class);
    }
}
