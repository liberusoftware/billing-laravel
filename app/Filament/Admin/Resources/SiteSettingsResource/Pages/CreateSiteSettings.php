<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\SiteSettingsResource\Pages;

use App\Filament\Admin\Resources\SiteSettingsResource;
use Filament\Resources\Pages\CreateRecord;
use Override;

class CreateSiteSettings extends CreateRecord
{
    #[Override]
    protected static string $resource = SiteSettingsResource::class;
}
