<?php

declare(strict_types=1);

namespace App\Modules\BlogModule;

use App\Modules\BaseModule;
use Illuminate\Support\Facades\Log;

class BlogModule extends BaseModule
{
    protected function onEnable(): void
    {
        Log::info('Blog module has been enabled.');
    }

    protected function onDisable(): void
    {
        Log::info('Blog module has been disabled.');
    }

    protected function onInstall(): void
    {
        Log::info('Blog module has been installed.');
    }

    protected function onUninstall(): void
    {
        Log::info('Blog module has been uninstalled.');
    }
}
