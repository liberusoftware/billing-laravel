<?php

declare(strict_types=1);

namespace Liberu\Billing\Communications\Filament\Resources\CommunicationNumberResource\Pages;

use Filament\Resources\Pages\ListRecords;
use Liberu\Billing\Communications\Filament\Resources\CommunicationNumberResource;

final class ListCommunicationNumbers extends ListRecords
{
    protected static string $resource = CommunicationNumberResource::class;
}
