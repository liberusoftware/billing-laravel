<?php

declare(strict_types=1);

namespace Liberu\Billing\Orders\Filament;

use Filament\Contracts\Plugin;
use Filament\Panel;
use Liberu\Billing\Orders\Filament\Resources\CartResource;
use Liberu\Billing\Orders\Filament\Resources\OrderResource;
use Liberu\Billing\Orders\Filament\Resources\QuoteResource;

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
        $panel->resources([OrderResource::class, QuoteResource::class, CartResource::class]);
    }

    public function boot(Panel $panel): void {}
}
