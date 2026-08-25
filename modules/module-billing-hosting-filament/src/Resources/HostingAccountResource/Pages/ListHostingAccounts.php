<?php

declare(strict_types=1);

namespace Liberu\Billing\Hosting\Filament\Resources\HostingAccountResource\Pages;

use Filament\Resources\Pages\ListRecords;
use Liberu\Billing\Hosting\Filament\Resources\HostingAccountResource;

final class ListHostingAccounts extends ListRecords
{
    protected static string $resource = HostingAccountResource::class;
}
