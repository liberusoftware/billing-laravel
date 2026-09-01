<?php

declare(strict_types=1);

namespace Liberu\Billing\Core;

use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Liberu\Billing\Core\Actions\CreateBillingRecord;
use Liberu\Billing\Core\Models\BillingAccount;
use Liberu\Billing\Core\Models\BillingContact;
use Liberu\Billing\Core\Models\BillingCurrency;
use Liberu\Billing\Core\Models\BillingSequence;
use Liberu\Billing\Core\Models\BillingSetting;
use Liberu\Billing\Core\Models\BillingTaxExemption;
use Liberu\Billing\Core\Models\BillingTaxProfile;
use Liberu\Billing\Core\Models\BillingTerm;
use Liberu\Billing\Core\Policies\BillingAccountPolicy;
use Liberu\Billing\Core\Policies\BillingCoreRecordPolicy;

final class BillingCoreServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(CreateBillingRecord::class);
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
        $this->loadTranslationsFrom(__DIR__.'/../lang', 'billing-core');
        Gate::policy(BillingAccount::class, BillingAccountPolicy::class);
        foreach ([BillingContact::class, BillingCurrency::class, BillingTaxProfile::class, BillingTaxExemption::class, BillingSequence::class, BillingTerm::class, BillingSetting::class] as $model) {
            Gate::policy($model, BillingCoreRecordPolicy::class);
        }
    }
}
