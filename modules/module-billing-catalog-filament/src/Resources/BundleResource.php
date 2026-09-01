<?php

declare(strict_types=1);

namespace Liberu\Billing\Catalog\Filament\Resources;

use Liberu\Billing\Catalog\Filament\Resources\BundleResource\Pages\CreateBundle;
use Liberu\Billing\Catalog\Filament\Resources\BundleResource\Pages\ListBundles;
use Liberu\Billing\Catalog\Models\Bundle;

final class BundleResource extends CatalogRecordResource
{
    protected static string|\UnitEnum|null $navigationGroup = 'Catalog & Pricing';

    protected static ?string $model = Bundle::class;

    protected static ?string $navigationLabel = 'Bundles';

    public static function getPages(): array
    {
        return ['index' => ListBundles::route('/'), 'create' => CreateBundle::route('/create')];
    }
}
