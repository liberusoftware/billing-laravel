<?php

declare(strict_types=1);

namespace Liberu\Billing\Communications\Filament\Resources\CommunicationUsageImportResource\Pages;

use Filament\Resources\Pages\ListRecords;
use Liberu\Billing\Communications\Filament\Resources\CommunicationUsageImportResource;

final class ListCommunicationUsageImports extends ListRecords
{
    protected static string $resource = CommunicationUsageImportResource::class;
}
