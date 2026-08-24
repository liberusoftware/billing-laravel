<?php

declare(strict_types=1);

namespace Liberu\Billing\Isp\Filament;

use Filament\Contracts\Plugin;
use Filament\Panel;
use Liberu\Billing\Isp\Filament\Resources\IspCapabilityResource;

final class IspFilamentPlugin implements Plugin
{
    public static function make(): self
    {
        return new self();
    }

    public function getId(): string
    {
        return 'liberu-billing-isp';
    }

    public function register(Panel $panel): void
    {
        $panel->resources([IspCapabilityResource::class]);
    }

    public function boot(Panel $panel): void {}
}
