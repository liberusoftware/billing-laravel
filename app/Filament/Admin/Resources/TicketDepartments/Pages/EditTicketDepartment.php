<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\TicketDepartments\Pages;

use App\Filament\Admin\Resources\TicketDepartments\TicketDepartmentResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Override;

class EditTicketDepartment extends EditRecord
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
            DeleteAction::make(),
        ];
    }
}
