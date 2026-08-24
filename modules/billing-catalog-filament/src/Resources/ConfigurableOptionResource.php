<?php

declare(strict_types=1);

namespace Liberu\Billing\Catalog\Filament\Resources;

use Liberu\Billing\Catalog\Models\ConfigurableOption;

final class ConfigurableOptionResource extends CatalogRecordResource
{
    protected static ?string $model = ConfigurableOption::class;

    protected static ?string $navigationLabel = 'Configurable options';
}
