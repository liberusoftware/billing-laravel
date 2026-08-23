<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\Currencies\Pages;

use App\Filament\Admin\Resources\Currencies\CurrencyResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Override;

class ListCurrencies extends ListRecords
{
    #[Override]
    protected static string $resource = CurrencyResource::class;

    #[Override]
    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
