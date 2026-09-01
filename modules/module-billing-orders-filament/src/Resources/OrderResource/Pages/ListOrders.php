<?php

declare(strict_types=1);

namespace Liberu\Billing\Orders\Filament\Resources\OrderResource\Pages;

use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Liberu\Billing\Orders\Filament\Resources\OrderResource;

final class ListOrders extends ListRecords
{
    protected static string $resource = OrderResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }
}
