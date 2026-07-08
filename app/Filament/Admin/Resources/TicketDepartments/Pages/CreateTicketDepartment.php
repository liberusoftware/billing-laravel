<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\TicketDepartments\Pages;

use App\Filament\Admin\Resources\TicketDepartments\TicketDepartmentResource;
use Filament\Resources\Pages\CreateRecord;
use Override;

class CreateTicketDepartment extends CreateRecord
{
    #[Override]
    protected static string $resource = TicketDepartmentResource::class;
}
