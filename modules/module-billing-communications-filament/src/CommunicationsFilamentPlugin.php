<?php

declare(strict_types=1);

namespace Liberu\Billing\Communications\Filament;

use Filament\Contracts\Plugin;
use Filament\Panel;
use Liberu\Billing\Communications\Filament\Resources\CommunicationNumberResource;
use Liberu\Billing\Communications\Filament\Resources\CommunicationProviderResource;
use Liberu\Billing\Communications\Filament\Resources\CommunicationServiceResource;
use Liberu\Billing\Communications\Filament\Resources\CommunicationUsageImportResource;
use Liberu\Billing\Communications\Filament\Resources\VoipAccountResource;

final class CommunicationsFilamentPlugin implements Plugin
{
    public static function make(): self
    {
        return new self();
    }

    public function getId(): string
    {
        return 'module-billing-communications-filament';
    }

    public function register(Panel $panel): void
    {
        $panel->resources([CommunicationServiceResource::class, CommunicationNumberResource::class, CommunicationProviderResource::class, CommunicationUsageImportResource::class, VoipAccountResource::class]);
    }

    public function boot(Panel $panel): void {}
}
