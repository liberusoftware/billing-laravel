<?php

declare(strict_types=1);

namespace App\Filament\Client\Resources\HostingAccounts\Pages;

use App\Filament\Client\Resources\HostingAccounts\HostingAccountResource;
use Filament\Resources\Pages\ListRecords;
use Override;

class ListHostingAccounts extends ListRecords
{
    #[Override]
    protected static string $resource = HostingAccountResource::class;
}
