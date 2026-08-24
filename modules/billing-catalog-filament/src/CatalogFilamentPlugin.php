<?php

declare(strict_types=1);

namespace Liberu\Billing\Catalog\Filament;

use Filament\Contracts\Plugin;
use Filament\Panel;
use Liberu\Billing\Catalog\Filament\Resources\AddonResource;
use Liberu\Billing\Catalog\Filament\Resources\BundleResource;
use Liberu\Billing\Catalog\Filament\Resources\ChannelResource;
use Liberu\Billing\Catalog\Filament\Resources\ConfigurableOptionResource;
use Liberu\Billing\Catalog\Filament\Resources\EligibilityResource;
use Liberu\Billing\Catalog\Filament\Resources\PlanResource;
use Liberu\Billing\Catalog\Filament\Resources\ProductResource;

final class CatalogFilamentPlugin implements Plugin
{
    public static function make(): self
    {
        return new self();
    }

    public function getId(): string
    {
        return 'liberu-billing-catalog';
    }

    public function register(Panel $panel): void
    {
        $panel->resources([ProductResource::class, PlanResource::class, AddonResource::class, BundleResource::class, ConfigurableOptionResource::class, EligibilityResource::class, ChannelResource::class]);
    }

    public function boot(Panel $panel): void {}
}
