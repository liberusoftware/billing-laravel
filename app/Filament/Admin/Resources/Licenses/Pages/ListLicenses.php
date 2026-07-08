<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\Licenses\Pages;

use App\Filament\Admin\Resources\Licenses\LicenseResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Override;

class ListLicenses extends ListRecords
{
    #[Override]
    protected static string $resource = LicenseResource::class;

    #[Override]
    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
