<?php

declare(strict_types=1);

namespace Liberu\Billing\Catalog\Filament\Resources\ProductResource\Pages;

use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Liberu\Billing\Catalog\Filament\Resources\ProductResource;

final class ListProducts extends ListRecords
{
    protected static string $resource = ProductResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }
}
