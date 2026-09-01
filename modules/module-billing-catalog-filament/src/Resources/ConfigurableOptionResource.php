<?php

declare(strict_types=1);

namespace Liberu\Billing\Catalog\Filament\Resources;

use Liberu\Billing\Catalog\Filament\Resources\ConfigurableOptionResource\Pages\CreateConfigurableOption;
use Liberu\Billing\Catalog\Filament\Resources\ConfigurableOptionResource\Pages\ListConfigurableOptions;
use Liberu\Billing\Catalog\Models\ConfigurableOption;

final class ConfigurableOptionResource extends CatalogRecordResource
{
    protected static string|\UnitEnum|null $navigationGroup = 'Catalog & Pricing';

    protected static ?string $model = ConfigurableOption::class;

    protected static ?string $navigationLabel = 'Configurable options';

    public static function getPages(): array
    {
        return ['index' => ListConfigurableOptions::route('/'), 'create' => CreateConfigurableOption::route('/create')];
    }
}
