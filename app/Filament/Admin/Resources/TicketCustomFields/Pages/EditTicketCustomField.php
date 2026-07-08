<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\TicketCustomFields\Pages;

use App\Filament\Admin\Resources\TicketCustomFields\TicketCustomFieldResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Override;

class EditTicketCustomField extends EditRecord
{
    #[Override]
    protected static string $resource = TicketCustomFieldResource::class;

    /**
     * @return array<int, mixed>
     */
    #[Override]
    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
