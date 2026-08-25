<?php

declare(strict_types=1);

namespace Liberu\Billing\Subscriptions\Filament;

use Filament\Contracts\Plugin;
use Filament\Panel;
use Liberu\Billing\Subscriptions\Filament\Resources\SubscriptionResource;

final class SubscriptionsFilamentPlugin implements Plugin
{
    public static function make(): self
    {
        return new self();
    }

    public function getId(): string
    {
        return 'module-billing-subscriptions-filament';
    }

    public function register(Panel $panel): void
    {
        $panel->resources([SubscriptionResource::class]);
    }

    public function boot(Panel $panel): void {}
}
