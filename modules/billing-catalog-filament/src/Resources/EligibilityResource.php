<?php

declare(strict_types=1);

namespace Liberu\Billing\Catalog\Filament\Resources;

use Liberu\Billing\Catalog\Models\Eligibility;

final class EligibilityResource extends CatalogRecordResource
{
    protected static ?string $model = Eligibility::class;

    protected static ?string $navigationLabel = 'Eligibility';
}
