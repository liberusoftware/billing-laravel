<?php

declare(strict_types=1);

namespace Liberu\Billing\Communications\Filament;

use Filament\Contracts\Plugin;
use Filament\Panel;
use Liberu\Billing\Communications\Filament\Resources\CommunicationNumberResource;
use Liberu\Billing\Communications\Filament\Resources\CommunicationUsageImportResource;

final class CommunicationsFilamentPlugin implements Plugin
{
    public static function make(): self
    {
        return new self();
    }

    public function getId(): string
    {
        return 'liberu-billing-communications';
    }

    public function register(Panel $panel): void
    {
        $panel->resources([CommunicationNumberResource::class, CommunicationUsageImportResource::class]);
    }

    public function boot(Panel $panel): void {}
}
