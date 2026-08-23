<?php

declare(strict_types=1);

namespace App\Filament\App\Resources\PartialPayments\Pages;

use App\Filament\App\Resources\PartialPayments\PartialPaymentResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Override;

class ListPartialPayments extends ListRecords
{
    #[Override]
    protected static string $resource = PartialPaymentResource::class;

    #[Override]
    protected function getActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
