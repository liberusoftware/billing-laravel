<?php

declare(strict_types=1);

namespace Liberu\Billing\Pricing\Filament;

use Filament\Contracts\Plugin;
use Filament\Panel;
use Liberu\Billing\Pricing\Filament\Resources\PricingContractResource;
use Liberu\Billing\Pricing\Filament\Resources\PricingDiscountResource;
use Liberu\Billing\Pricing\Filament\Resources\PricingPlanResource;
use Liberu\Billing\Pricing\Filament\Resources\PricingSnapshotResource;

final class PricingFilamentPlugin implements Plugin
{
    public static function make(): self
    {
        return new self();
    }

    public function getId(): string
    {
        return 'module-billing-pricing-filament';
    }

    public function register(Panel $panel): void
    {
        $panel->resources([PricingPlanResource::class, PricingContractResource::class, PricingDiscountResource::class, PricingSnapshotResource::class]);
    }

    public function boot(Panel $panel): void {}
}
