<?php

declare(strict_types=1);

namespace Liberu\Billing\Reporting\Livewire;

use Illuminate\Support\ServiceProvider;
use Liberu\Billing\Reporting\Livewire\Components\CustomerBillingSummary;
use Liberu\Billing\Reporting\Livewire\Components\ReportingDashboard;
use Liberu\Billing\Reporting\Livewire\Components\ReportingMetrics;
use Liberu\Billing\Reporting\Livewire\Components\ReportingSnapshots;
use Livewire\Livewire;

final class ReportingLivewireServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'module-billing-reporting-livewire');
        Livewire::component('module-billing-reporting::metrics', ReportingMetrics::class);
        Livewire::component('module-billing-reporting::dashboard', ReportingDashboard::class);
        Livewire::component('module-billing-reporting::snapshots', ReportingSnapshots::class);
        Livewire::component('module-billing-reporting::customer-summary', CustomerBillingSummary::class);
    }
}
