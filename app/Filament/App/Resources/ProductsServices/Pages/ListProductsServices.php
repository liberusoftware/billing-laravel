<?php

declare(strict_types=1);

namespace App\Filament\App\Resources\ProductsServices\Pages;

use App\Filament\App\Resources\ProductsServices\ProductsServiceResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Override;

class ListProductsServices extends ListRecords
{
    #[Override]
    protected static string $resource = ProductsServiceResource::class;

    #[Override]
    protected function getActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
