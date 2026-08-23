<?php

declare(strict_types=1);

namespace App\Filament\App\Resources\ProductsServices\Pages;

use App\Filament\App\Resources\ProductsServices\ProductsServiceResource;
use Filament\Resources\Pages\CreateRecord;
use Override;

class CreateProductsService extends CreateRecord
{
    #[Override]
    protected static string $resource = ProductsServiceResource::class;
}
