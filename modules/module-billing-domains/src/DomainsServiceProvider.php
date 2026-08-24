<?php

declare(strict_types=1);

namespace Liberu\Billing\Domains;

use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Liberu\Billing\Domains\Models\Domain;
use Liberu\Billing\Domains\Policies\DomainPolicy;

final class DomainsServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        Gate::policy(Domain::class, DomainPolicy::class);
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
    }
}
