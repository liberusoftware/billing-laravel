<?php

declare(strict_types=1);

namespace Liberu\Billing\Usage\Api;

use Illuminate\Support\ServiceProvider;

final class UsageApiServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->loadRoutesFrom(__DIR__.'/../routes/api.php');
    }
}
