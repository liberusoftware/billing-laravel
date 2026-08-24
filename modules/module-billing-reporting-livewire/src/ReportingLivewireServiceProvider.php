<?php

declare(strict_types=1);

namespace Liberu\Billing\Reporting\Livewire;

use Illuminate\Support\ServiceProvider;
use Liberu\Billing\Reporting\Livewire\Components\ReportingMetrics;
use Livewire\Livewire;

final class ReportingLivewireServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'module-billing-reporting-livewire');
        Livewire::component('module-billing-reporting::metrics', ReportingMetrics::class);
    }
}
