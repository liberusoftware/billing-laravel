<?php

declare(strict_types=1);

namespace Liberu\Billing\Core\Filament;

use Filament\Contracts\Plugin;
use Filament\Panel;
use Liberu\Billing\Core\Filament\Resources\BillingAccountResource;
use Liberu\Billing\Core\Filament\Resources\BillingContactResource;
use Liberu\Billing\Core\Filament\Resources\BillingCurrencyResource;
use Liberu\Billing\Core\Filament\Resources\BillingSequenceResource;
use Liberu\Billing\Core\Filament\Resources\BillingSettingResource;
use Liberu\Billing\Core\Filament\Resources\BillingTaxExemptionResource;
use Liberu\Billing\Core\Filament\Resources\BillingTaxProfileResource;
use Liberu\Billing\Core\Filament\Resources\BillingTermResource;

final class BillingCoreFilamentPlugin implements Plugin
{
    public static function make(): self
    {
        return new self();
    }

    public function getId(): string
    {
        return 'module-billing-core-filament';
    }

    public function register(Panel $panel): void
    {
        $panel->resources([
            BillingAccountResource::class, BillingContactResource::class, BillingCurrencyResource::class,
            BillingTaxProfileResource::class, BillingTaxExemptionResource::class, BillingSequenceResource::class, BillingTermResource::class, BillingSettingResource::class,
        ]);
    }

    public function boot(Panel $panel): void {}
}
