<?php

declare(strict_types=1);

namespace Liberu\Billing\Provisioning\Filament;

use Filament\Contracts\Plugin;
use Filament\Panel;
use Liberu\Billing\Provisioning\Filament\Resources\ProvisioningOperationResource;
use Liberu\Billing\Provisioning\Filament\Resources\ProvisionedServiceResource;

final class ProvisioningFilamentPlugin implements Plugin
{
    public static function make(): self
    {
        return new self();
    }

    public function getId(): string
    {
        return 'liberu-billing-provisioning';
    }

    public function register(Panel $panel): void
    {
        $panel->resources([ProvisionedServiceResource::class, ProvisioningOperationResource::class]);
    }

    public function boot(Panel $panel): void {}
}
