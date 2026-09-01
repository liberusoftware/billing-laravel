<?php

declare(strict_types=1);

namespace Liberu\Billing\Catalog\Filament\Resources;

use Liberu\Billing\Catalog\Filament\Resources\AddonResource\Pages\CreateAddon;
use Liberu\Billing\Catalog\Filament\Resources\AddonResource\Pages\ListAddons;
use Liberu\Billing\Catalog\Models\Addon;

final class AddonResource extends CatalogRecordResource
{
    protected static string|\UnitEnum|null $navigationGroup = 'Catalog & Pricing';

    protected static ?string $model = Addon::class;

    protected static ?string $navigationLabel = 'Add-ons';

    public static function getPages(): array
    {
        return ['index' => ListAddons::route('/'), 'create' => CreateAddon::route('/create')];
    }
}
