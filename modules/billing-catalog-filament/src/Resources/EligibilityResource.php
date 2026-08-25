<?php

declare(strict_types=1);

namespace Liberu\Billing\Catalog\Filament\Resources;

use Liberu\Billing\Catalog\Filament\Resources\EligibilityResource\Pages\CreateEligibility;
use Liberu\Billing\Catalog\Filament\Resources\EligibilityResource\Pages\ListEligibility;
use Liberu\Billing\Catalog\Models\Eligibility;

final class EligibilityResource extends CatalogRecordResource
{
    protected static ?string $model = Eligibility::class;

    protected static ?string $navigationLabel = 'Eligibility';

    public static function getPages(): array
    {
        return ['index' => ListEligibility::route('/'), 'create' => CreateEligibility::route('/create')];
    }
}
