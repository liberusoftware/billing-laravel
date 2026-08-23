<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\Currencies\Pages;

use App\Filament\Admin\Resources\Currencies\CurrencyResource;
use Filament\Resources\Pages\CreateRecord;
use Override;

class CreateCurrency extends CreateRecord
{
    #[Override]
    protected static string $resource = CurrencyResource::class;
}
