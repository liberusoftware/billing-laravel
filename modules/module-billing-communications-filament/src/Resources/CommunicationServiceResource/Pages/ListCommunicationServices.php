<?php

declare(strict_types=1);

namespace Liberu\Billing\Communications\Filament\Resources\CommunicationServiceResource\Pages;

use Filament\Resources\Pages\ListRecords;
use Liberu\Billing\Communications\Filament\Resources\CommunicationServiceResource;

final class ListCommunicationServices extends ListRecords
{
    protected static string $resource = CommunicationServiceResource::class;
}
