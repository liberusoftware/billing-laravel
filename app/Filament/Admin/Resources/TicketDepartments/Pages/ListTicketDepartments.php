<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\TicketDepartments\Pages;

use App\Filament\Admin\Resources\TicketDepartments\TicketDepartmentResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Override;

class ListTicketDepartments extends ListRecords
{
    #[Override]
    protected static string $resource = TicketDepartmentResource::class;

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
