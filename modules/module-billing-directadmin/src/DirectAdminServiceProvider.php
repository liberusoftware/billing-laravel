<?php

declare(strict_types=1);

namespace Liberu\Billing\DirectAdmin;

use Illuminate\Support\ServiceProvider;
use Liberu\Billing\Hosting\Services\HostingDriverRegistry;

final class DirectAdminServiceProvider extends ServiceProvider
{
    public function boot(HostingDriverRegistry $drivers): void
    {
        $drivers->register($this->app->make(DirectAdminDriver::class));
    }
}
