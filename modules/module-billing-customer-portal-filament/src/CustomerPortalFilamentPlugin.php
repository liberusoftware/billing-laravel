<?php

declare(strict_types=1);

namespace Liberu\Billing\CustomerPortal\Filament;

use Filament\Contracts\Plugin;
use Filament\Panel;
use Liberu\Billing\CustomerPortal\Filament\Resources\PortalItemResource;
use Liberu\Billing\CustomerPortal\Filament\Resources\PortalRequestResource;

final class CustomerPortalFilamentPlugin implements Plugin
{
    public static function make(): self
    {
        return new self();
    }

    public function getId(): string
    {
        return 'module-billing-customer-portal-filament';
    }

    public function register(Panel $panel): void
    {
        $panel->resources([PortalRequestResource::class, PortalItemResource::class]);
    }

    public function boot(Panel $panel): void {}
}
