<?php

declare(strict_types=1);

namespace Liberu\Billing\Orders\Filament;

use Filament\Contracts\Plugin;
use Filament\Panel;
use Liberu\Billing\Orders\Filament\Resources\OrderResource;

final class OrdersFilamentPlugin implements Plugin
{
    public static function make(): self
    {
        return new self();
    }

    public function getId(): string
    {
        return 'liberu-billing-orders';
    }

    public function register(Panel $panel): void
    {
        $panel->resources([OrderResource::class]);
    }

    public function boot(Panel $panel): void {}
}
