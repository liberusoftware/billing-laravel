<?php

declare(strict_types=1);

namespace Liberu\Billing\Catalog\Filament\Resources\ProductResource\Pages;

use Filament\Resources\Pages\CreateRecord;
use Liberu\Billing\Catalog\Filament\Resources\ProductResource;

final class CreateProduct extends CreateRecord
{
    protected static string $resource = ProductResource::class;
}
