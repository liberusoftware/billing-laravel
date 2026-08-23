<?php

declare(strict_types=1);

namespace App\Filament\App\Resources\HostingAccounts\Pages;

use App\Filament\App\Resources\HostingAccounts\HostingAccountResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Override;

class ListHostingAccounts extends ListRecords
{
    #[Override]
    protected static string $resource = HostingAccountResource::class;

    #[Override]
    protected function getActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
