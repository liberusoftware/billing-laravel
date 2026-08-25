<?php

declare(strict_types=1);

namespace Liberu\Billing\Communications\Filament\Resources\CommunicationProviderResource\Pages;

use Filament\Resources\Pages\ListRecords;
use Liberu\Billing\Communications\Filament\Resources\CommunicationProviderResource;

final class ListCommunicationProviders extends ListRecords
{
    protected static string $resource = CommunicationProviderResource::class;
}
