<?php

declare(strict_types=1);

namespace Liberu\Billing\Usage\Livewire;

use Illuminate\Support\ServiceProvider;
use Liberu\Billing\Usage\Livewire\Components\MeterList;
use Liberu\Billing\Usage\Livewire\Components\UsageRecords;
use Livewire\Livewire;

final class UsageLivewireServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'module-billing-usage-livewire');
        Livewire::component('module-billing-usage::meter-list', MeterList::class);
        Livewire::component('module-billing-usage::records', UsageRecords::class);
    }
}
