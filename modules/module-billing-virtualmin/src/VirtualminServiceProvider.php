<?php

declare(strict_types=1);

namespace Liberu\Billing\Virtualmin;

use Illuminate\Support\ServiceProvider;
use Liberu\Billing\Hosting\Services\HostingDriverRegistry;

final class VirtualminServiceProvider extends ServiceProvider
{
    public function boot(HostingDriverRegistry $drivers): void
    {
        $drivers->register($this->app->make(VirtualminDriver::class));
        $drivers->register($this->app->make(VirtualminProDriver::class));
    }
}
