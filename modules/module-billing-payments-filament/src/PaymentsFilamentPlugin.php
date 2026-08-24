<?php

declare(strict_types=1);

namespace Liberu\Billing\Payments\Filament;

use Filament\Contracts\Plugin;
use Filament\Panel;
use Liberu\Billing\Payments\Filament\Resources\PaymentResource;

final class PaymentsFilamentPlugin implements Plugin
{
    public static function make(): self
    {
        return new self();
    }

    public function getId(): string
    {
        return 'liberu-billing-payments';
    }

    public function register(Panel $panel): void
    {
        $panel->resources([PaymentResource::class]);
    }

    public function boot(Panel $panel): void {}
}
