<?php

declare(strict_types=1);

namespace App\Filament\App\Resources\ProductsServices\Pages;

use App\Filament\App\Resources\ProductsServices\ProductsServiceResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Override;

class EditProductsService extends EditRecord
{
    #[Override]
    protected static string $resource = ProductsServiceResource::class;

    #[Override]
    protected function getActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
