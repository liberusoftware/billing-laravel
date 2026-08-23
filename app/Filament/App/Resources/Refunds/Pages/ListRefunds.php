<?php

declare(strict_types=1);

namespace App\Filament\App\Resources\Refunds\Pages;

use App\Filament\App\Resources\Refunds\RefundResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Override;

class ListRefunds extends ListRecords
{
    #[Override]
    protected static string $resource = RefundResource::class;

    #[Override]
    protected function getActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
