<?php

declare(strict_types=1);

namespace Liberu\Billing\Catalog\Filament\Resources\AddonResource\Pages;

use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Liberu\Billing\Catalog\Filament\Resources\AddonResource;

final class ListAddons extends ListRecords
{
    protected static string $resource = AddonResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }
}
