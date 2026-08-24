<?php

declare(strict_types=1);

namespace Liberu\Billing\CustomerPortal\Filament;

use Filament\Contracts\Plugin;
use Filament\Panel;
use Liberu\Billing\CustomerPortal\Filament\Resources\PortalItemResource;

final class CustomerPortalFilamentPlugin implements Plugin
{
    public static function make(): self
    {
        return new self();
    }

    public function getId(): string
    {
        return 'liberu-billing-customer-portal';
    }

    public function register(Panel $panel): void
    {
        $panel->resources([PortalItemResource::class]);
    }

    public function boot(Panel $panel): void {}
}
