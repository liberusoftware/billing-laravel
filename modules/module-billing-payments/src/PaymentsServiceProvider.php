<?php

declare(strict_types=1);

namespace Liberu\Billing\Payments;

use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Liberu\Billing\Payments\Models\Payment;
use Liberu\Billing\Payments\Models\PaymentAllocation;
use Liberu\Billing\Payments\Models\PaymentDispute;
use Liberu\Billing\Payments\Models\PaymentMandate;
use Liberu\Billing\Payments\Models\PaymentMethod;
use Liberu\Billing\Payments\Models\PaymentReconciliation;
use Liberu\Billing\Payments\Models\PaymentRefund;
use Liberu\Billing\Payments\Policies\PaymentMandatePolicy;
use Liberu\Billing\Payments\Policies\PaymentMethodPolicy;
use Liberu\Billing\Payments\Policies\PaymentPolicy;
use Liberu\Billing\Payments\Policies\PaymentRecordPolicy;
use Liberu\Billing\Payments\Services\GatewayManager;

final class PaymentsServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(GatewayManager::class);
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
        Gate::policy(Payment::class, PaymentPolicy::class);
        Gate::policy(PaymentMethod::class, PaymentMethodPolicy::class);
        Gate::policy(PaymentMandate::class, PaymentMandatePolicy::class);
        foreach ([PaymentAllocation::class, PaymentDispute::class, PaymentReconciliation::class, PaymentRefund::class] as $model) {
            Gate::policy($model, PaymentRecordPolicy::class);
        }
    }
}
