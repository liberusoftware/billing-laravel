<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\OrderFormTemplates\Pages;

use App\Filament\Admin\Resources\OrderFormTemplates\OrderFormTemplateResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Override;

class ListOrderFormTemplates extends ListRecords
{
    #[Override]
    protected static string $resource = OrderFormTemplateResource::class;

    #[Override]
    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
