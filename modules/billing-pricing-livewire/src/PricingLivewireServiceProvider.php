<?php

declare(strict_types=1);

namespace Liberu\Billing\Pricing\Livewire;

use Illuminate\Support\ServiceProvider;
use Liberu\Billing\Pricing\Livewire\Components\PricingPlanList;
use Livewire\Livewire;

final class PricingLivewireServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'billing-pricing-livewire');
        Livewire::component('billing-pricing::plan-list', PricingPlanList::class);
    }
}
