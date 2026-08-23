<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\TicketCustomFields\Pages;

use App\Filament\Admin\Resources\TicketCustomFields\TicketCustomFieldResource;
use Filament\Resources\Pages\CreateRecord;
use Override;

class CreateTicketCustomField extends CreateRecord
{
    #[Override]
    protected static string $resource = TicketCustomFieldResource::class;
}
