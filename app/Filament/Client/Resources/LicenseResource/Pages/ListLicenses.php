<?php

declare(strict_types=1);

namespace App\Filament\Client\Resources\LicenseResource\Pages;

use App\Filament\Client\Resources\LicenseResource;
use Filament\Resources\Pages\ListRecords;
use Override;

class ListLicenses extends ListRecords
{
    #[Override]
    protected static string $resource = LicenseResource::class;
}
