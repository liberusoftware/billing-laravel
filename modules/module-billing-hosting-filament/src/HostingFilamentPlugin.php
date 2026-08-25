<?php

declare(strict_types=1);

namespace Liberu\Billing\Hosting\Filament;

use Filament\Contracts\Plugin;
use Filament\Panel;
use Liberu\Billing\Hosting\Filament\Resources\HostingAccountResource;
use Liberu\Billing\Hosting\Filament\Resources\HostingCapabilityResource;

final class HostingFilamentPlugin implements Plugin
{
    public static function make(): self
    {
        return new self();
    }

    public function getId(): string
    {
        return 'liberu-billing-hosting';
    }

    public function register(Panel $panel): void
    {
        $panel->resources([HostingAccountResource::class, HostingCapabilityResource::class]);
    }

    public function boot(Panel $panel): void {}
}
