<?php

declare(strict_types=1);

namespace App\Filament\App\Resources\HostingAccounts\Pages;

use App\Filament\App\Resources\HostingAccounts\HostingAccountResource;
use Filament\Resources\Pages\CreateRecord;
use Override;

class CreateHostingAccount extends CreateRecord
{
    #[Override]
    protected static string $resource = HostingAccountResource::class;
}
