<?php

declare(strict_types=1);

namespace Liberu\Billing\Communications;

use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Liberu\Billing\Communications\Models\CallDetailRecord;
use Liberu\Billing\Communications\Models\CallRateRule;
use Liberu\Billing\Communications\Models\CommunicationNumber;
use Liberu\Billing\Communications\Models\CommunicationProvider;
use Liberu\Billing\Communications\Models\CommunicationService;
use Liberu\Billing\Communications\Models\CommunicationUsageImport;
use Liberu\Billing\Communications\Models\VoipAccount;
use Liberu\Billing\Communications\Models\VoipFraudAlert;
use Liberu\Billing\Communications\Policies\CommunicationRecordPolicy;
use Liberu\Billing\Communications\Policies\CommunicationServicePolicy;
use Liberu\Billing\Communications\Services\VoiceProviderRegistry;

final class CommunicationsServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(VoiceProviderRegistry::class);
    }

    public function boot(): void
    {
        Gate::policy(CommunicationService::class, CommunicationServicePolicy::class);
        foreach ([CommunicationNumber::class, CommunicationProvider::class, CommunicationUsageImport::class, CallDetailRecord::class, CallRateRule::class, VoipAccount::class, VoipFraudAlert::class] as $model) {
            Gate::policy($model, CommunicationRecordPolicy::class);
        }
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
    }
}
