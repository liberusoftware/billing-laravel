<?php

declare(strict_types=1);

namespace Liberu\Billing\Communications;

use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Liberu\Billing\Communications\Models\CommunicationService;
use Liberu\Billing\Communications\Policies\CommunicationServicePolicy;

final class CommunicationsServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        Gate::policy(CommunicationService::class, CommunicationServicePolicy::class);
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
    }
}
