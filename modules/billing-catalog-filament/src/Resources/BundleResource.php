<?php

declare(strict_types=1);

namespace Liberu\Billing\Catalog\Filament\Resources;

use Liberu\Billing\Catalog\Models\Bundle;

final class BundleResource extends CatalogRecordResource
{
    protected static ?string $model = Bundle::class;

    protected static ?string $navigationLabel = 'Bundles';
}
