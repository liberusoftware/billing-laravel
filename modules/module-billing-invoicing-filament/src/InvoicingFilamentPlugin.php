<?php

declare(strict_types=1);

namespace Liberu\Billing\Invoicing\Filament;

use Filament\Contracts\Plugin;
use Filament\Panel;
use Liberu\Billing\Invoicing\Filament\Resources\InvoiceLineResource;
use Liberu\Billing\Invoicing\Filament\Resources\InvoiceResource;
use Liberu\Billing\Invoicing\Filament\Resources\InvoiceScheduleResource;
use Liberu\Billing\Invoicing\Filament\Resources\InvoiceSupportResource;

final class InvoicingFilamentPlugin implements Plugin
{
    public static function make(): self
    {
        return new self();
    }

    public function getId(): string
    {
        return 'module-billing-invoicing-filament';
    }

    public function register(Panel $panel): void
    {
        $panel->resources([InvoiceResource::class, InvoiceLineResource::class, InvoiceScheduleResource::class, InvoiceSupportResource::class]);
    }

    public function boot(Panel $panel): void {}
}
