<?php

declare(strict_types=1);

namespace Liberu\Billing\Domains\Filament\Resources\DomainResource\Pages;

use Filament\Resources\Pages\ListRecords;
use Liberu\Billing\Domains\Filament\Resources\DomainResource;

final class ListDomains extends ListRecords
{
    protected static string $resource = DomainResource::class;
}
