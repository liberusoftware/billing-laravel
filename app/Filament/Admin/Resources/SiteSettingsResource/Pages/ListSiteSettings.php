<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\SiteSettingsResource\Pages;

use App\Filament\Admin\Resources\SiteSettingsResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Override;

class ListSiteSettings extends ListRecords
{
    #[Override]
    protected static string $resource = SiteSettingsResource::class;

    #[Override]
    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
