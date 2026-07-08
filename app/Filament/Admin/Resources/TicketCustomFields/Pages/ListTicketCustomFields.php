<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\TicketCustomFields\Pages;

use App\Filament\Admin\Resources\TicketCustomFields\TicketCustomFieldResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Override;

class ListTicketCustomFields extends ListRecords
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
            CreateAction::make(),
        ];
    }
}
