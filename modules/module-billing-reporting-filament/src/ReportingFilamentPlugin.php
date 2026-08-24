<?php

declare(strict_types=1);

namespace Liberu\Billing\Reporting\Filament;

use Filament\Contracts\Plugin;
use Filament\Panel;
use Liberu\Billing\Reporting\Filament\Resources\ReportingMetricResource;

final class ReportingFilamentPlugin implements Plugin
{
    public static function make(): self
    {
        return new self();
    }

    public function getId(): string
    {
        return 'liberu-billing-reporting';
    }

    public function register(Panel $panel): void
    {
        $panel->resources([ReportingMetricResource::class]);
    }

    public function boot(Panel $panel): void {}
}
