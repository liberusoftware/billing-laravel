<?php

declare(strict_types=1);

namespace Liberu\Billing\Collections\Filament;

use Filament\Contracts\Plugin;
use Filament\Panel;
use Liberu\Billing\Collections\Filament\Resources\CollectionCaseResource;

final class CollectionsFilamentPlugin implements Plugin
{
    public static function make(): self
    {
        return new self();
    }

    public function getId(): string
    {
        return 'liberu-billing-collections';
    }

    public function register(Panel $panel): void
    {
        $panel->resources([CollectionCaseResource::class]);
    }

    public function boot(Panel $panel): void {}
}
