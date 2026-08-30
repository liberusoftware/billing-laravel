<?php

declare(strict_types=1);

namespace Liberu\Billing\Plesk;

use Illuminate\Support\ServiceProvider;
use Liberu\Billing\Hosting\Services\HostingDriverRegistry;

final class PleskServiceProvider extends ServiceProvider
{
    public function boot(HostingDriverRegistry $drivers): void
    {
        $drivers->register($this->app->make(PleskDriver::class));
    }
}
