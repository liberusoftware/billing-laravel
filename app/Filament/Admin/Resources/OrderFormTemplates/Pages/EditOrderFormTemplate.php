<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\OrderFormTemplates\Pages;

use App\Filament\Admin\Resources\OrderFormTemplates\OrderFormTemplateResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Override;

class EditOrderFormTemplate extends EditRecord
{
    #[Override]
    protected static string $resource = OrderFormTemplateResource::class;

    #[Override]
    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
