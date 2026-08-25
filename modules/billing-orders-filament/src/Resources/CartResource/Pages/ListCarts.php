<?php

declare(strict_types=1);

namespace Liberu\Billing\Orders\Filament\Resources\CartResource\Pages;

use Filament\Resources\Pages\ListRecords;
use Liberu\Billing\Orders\Filament\Resources\CartResource;

final class ListCarts extends ListRecords
{
    protected static string $resource = CartResource::class;
}
