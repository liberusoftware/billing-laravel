<?php

declare(strict_types=1);

namespace Liberu\Billing\Collections;

use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Liberu\Billing\Collections\Models\CollectionCase;
use Liberu\Billing\Collections\Policies\CollectionCasePolicy;

final class CollectionsServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
        Gate::policy(CollectionCase::class, CollectionCasePolicy::class);
    }
}
