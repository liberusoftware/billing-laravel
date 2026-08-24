<?php

declare(strict_types=1);

namespace Liberu\Billing\Domains\Filament;

use Filament\Contracts\Plugin;
use Filament\Panel;
use Liberu\Billing\Domains\Filament\Resources\DnsRecordResource;
use Liberu\Billing\Domains\Filament\Resources\DomainContactResource;
use Liberu\Billing\Domains\Filament\Resources\DomainResource;

final class DomainsFilamentPlugin implements Plugin
{
    public static function make(): self
    {
        return new self();
    }

    public function getId(): string
    {
        return 'liberu-billing-domains';
    }

    public function register(Panel $panel): void
    {
        $panel->resources([DomainResource::class, DomainContactResource::class, DnsRecordResource::class]);
    }

    public function boot(Panel $panel): void {}
}
