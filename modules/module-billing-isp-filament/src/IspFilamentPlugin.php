<?php

declare(strict_types=1);

namespace Liberu\Billing\Isp\Filament;

use Filament\Contracts\Plugin;
use Filament\Panel;
use Liberu\Billing\Isp\Filament\Resources\AccessServiceResource;
use Liberu\Billing\Isp\Filament\Resources\IspCapabilityResource;

final class IspFilamentPlugin implements Plugin
{
    public static function make(): self
    {
        return new self();
    }

    public function getId(): string
    {
        return 'module-billing-isp-filament';
    }

    public function register(Panel $panel): void
    {
        $panel->resources([AccessServiceResource::class, IspCapabilityResource::class]);
    }

    public function boot(Panel $panel): void {}
}
