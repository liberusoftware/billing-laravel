<?php

declare(strict_types=1);

namespace Liberu\Billing\Usage\Filament;

use Filament\Contracts\Plugin;
use Filament\Panel;
use Liberu\Billing\Usage\Filament\Resources\MeterResource;
use Liberu\Billing\Usage\Filament\Resources\UsageRecordResource;

final class UsageFilamentPlugin implements Plugin
{
    public static function make(): self
    {
        return new self();
    }

    public function getId(): string
    {
        return 'module-billing-usage-filament';
    }

    public function register(Panel $panel): void
    {
        $panel->resources([MeterResource::class, UsageRecordResource::class]);
    }

    public function boot(Panel $panel): void {}
}
