<?php

declare(strict_types=1);

namespace Liberu\Billing\Domains\Filament\Resources\DomainTldResource\Pages;

use Filament\Resources\Pages\ListRecords;
use Liberu\Billing\Domains\Filament\Resources\DomainTldResource;

final class ListDomainTlds extends ListRecords
{
    protected static string $resource = DomainTldResource::class;
}
