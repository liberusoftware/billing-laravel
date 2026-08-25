<?php

declare(strict_types=1);

namespace Liberu\Billing\CustomerPortal\Filament\Resources\PortalRequestResource\Pages;

use Filament\Resources\Pages\ListRecords;
use Liberu\Billing\CustomerPortal\Filament\Resources\PortalRequestResource;

final class ListPortalRequests extends ListRecords
{
    protected static string $resource = PortalRequestResource::class;
}
