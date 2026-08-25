<?php

declare(strict_types=1);

namespace Liberu\Billing\Catalog\Filament\Resources\BundleResource\Pages;

use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Liberu\Billing\Catalog\Filament\Resources\BundleResource;

final class ListBundles extends ListRecords
{
    protected static string $resource = BundleResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }
}
