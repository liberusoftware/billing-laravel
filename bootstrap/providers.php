<?php

use App\Providers\AppServiceProvider;
use App\Providers\AuthServiceProvider;
use App\Providers\EventServiceProvider;
use App\Providers\Filament\AdminPanelProvider;
use App\Providers\Filament\AppPanelProvider;
use Liberu\Foundation\ModuleManager\ModuleManagerServiceProvider;

return [
    AppServiceProvider::class,
    AuthServiceProvider::class,
    EventServiceProvider::class,
    ModuleManagerServiceProvider::class,
    AdminPanelProvider::class,
    AppPanelProvider::class,
];
