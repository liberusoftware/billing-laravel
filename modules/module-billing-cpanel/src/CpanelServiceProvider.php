<?php

declare(strict_types=1);

namespace Liberu\Billing\Cpanel;

use Illuminate\Support\ServiceProvider;
use Liberu\Billing\Hosting\Services\HostingDriverRegistry;

final class CpanelServiceProvider extends ServiceProvider
{
    public function boot(HostingDriverRegistry $drivers): void
    {
        $drivers->register($this->app->make(CpanelDriver::class));
    }
}
