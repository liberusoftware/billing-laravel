<?php

declare(strict_types=1);

namespace Liberu\Billing\Catalog\Filament\Resources;

use Liberu\Billing\Catalog\Models\Addon;

final class AddonResource extends CatalogRecordResource
{
    protected static ?string $model = Addon::class;

    protected static ?string $navigationLabel = 'Add-ons';
}
