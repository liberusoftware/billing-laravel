<?php

declare(strict_types=1);

namespace Liberu\Billing\Domains;

use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Liberu\Billing\Domains\Models\DnsRecord;
use Liberu\Billing\Domains\Models\Domain;
use Liberu\Billing\Domains\Models\DomainContact;
use Liberu\Billing\Domains\Models\EppOperation;
use Liberu\Billing\Domains\Policies\DomainPolicy;
use Liberu\Billing\Domains\Policies\DomainSupportPolicy;
use Liberu\Billing\Domains\Services\RegistrarManager;

final class DomainsServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        Gate::policy(Domain::class, DomainPolicy::class);
        foreach ([DomainContact::class, DnsRecord::class, EppOperation::class] as $model) {
            Gate::policy($model, DomainSupportPolicy::class);
        }
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
    }

    public function register(): void
    {
        $this->app->singleton(RegistrarManager::class);
    }
}
