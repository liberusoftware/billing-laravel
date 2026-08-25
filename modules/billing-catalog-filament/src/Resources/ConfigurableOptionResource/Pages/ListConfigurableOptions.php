<?php

declare(strict_types=1);

namespace Liberu\Billing\Catalog\Filament\Resources\ConfigurableOptionResource\Pages;

use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Liberu\Billing\Catalog\Filament\Resources\ConfigurableOptionResource;

final class ListConfigurableOptions extends ListRecords
{
    protected static string $resource = ConfigurableOptionResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }
}
