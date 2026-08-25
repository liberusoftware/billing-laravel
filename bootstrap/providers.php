<?php

use App\Providers\AppServiceProvider;
use App\Providers\AuthServiceProvider;
use App\Providers\EventServiceProvider;
use App\Providers\Filament\AdminPanelProvider;
use App\Providers\Filament\AppPanelProvider;
use App\Providers\Filament\ClientPanelProvider;
use App\Providers\FortifyServiceProvider;
use App\Providers\JetstreamServiceProvider;
use App\Providers\RouteServiceProvider;
use Liberu\Foundation\ModuleManager\ModuleManagerServiceProvider;

return [
    ModuleManagerServiceProvider::class,
    AppServiceProvider::class,
    AuthServiceProvider::class,
    EventServiceProvider::class,
    FortifyServiceProvider::class,
    JetstreamServiceProvider::class,
    RouteServiceProvider::class,
    AdminPanelProvider::class,
    AppPanelProvider::class,
    ClientPanelProvider::class,
];
