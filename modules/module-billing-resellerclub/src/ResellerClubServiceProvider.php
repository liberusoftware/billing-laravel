<?php

declare(strict_types=1);

namespace Liberu\Billing\ResellerClub;

use Illuminate\Support\ServiceProvider;
use Liberu\Billing\Domains\Services\RegistrarManager;

final class ResellerClubServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->afterResolving(RegistrarManager::class, fn (RegistrarManager $manager): RegistrarManager => tap($manager)->register('resellerclub', $this->app->make(ResellerClubRegistrar::class)));
    }
}
