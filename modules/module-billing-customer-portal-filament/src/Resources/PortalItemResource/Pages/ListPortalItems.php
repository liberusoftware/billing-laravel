<?php

declare(strict_types=1);

namespace Liberu\Billing\CustomerPortal\Filament\Resources\PortalItemResource\Pages;

use Filament\Resources\Pages\ListRecords;
use Liberu\Billing\CustomerPortal\Filament\Resources\PortalItemResource;

final class ListPortalItems extends ListRecords
{
    protected static string $resource = PortalItemResource::class;
}
