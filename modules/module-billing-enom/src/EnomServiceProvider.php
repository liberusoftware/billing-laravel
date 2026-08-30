<?php

declare(strict_types=1);

namespace Liberu\Billing\Enom;

use Illuminate\Support\ServiceProvider;
use Liberu\Billing\Domains\Services\RegistrarManager;

final class EnomServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->afterResolving(RegistrarManager::class, fn (RegistrarManager $manager): RegistrarManager => tap($manager)->register('enom', $this->app->make(EnomRegistrar::class)));
    }
}
