<?php

declare(strict_types=1);

namespace Liberu\Billing\LiberuControlPanel;

use Illuminate\Support\ServiceProvider;
use Liberu\Billing\Hosting\Services\HostingDriverRegistry;

final class LiberuControlPanelServiceProvider extends ServiceProvider
{
    public function boot(HostingDriverRegistry $drivers): void
    {
        $drivers->register($this->app->make(LiberuControlPanelDriver::class));
    }
}
