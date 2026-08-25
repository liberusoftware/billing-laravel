<?php

declare(strict_types=1);

namespace Liberu\Billing\Core\Filament\Resources;

use Liberu\Billing\Core\Filament\Resources\BillingTaxProfileResource\Pages\CreateBillingTaxProfile;
use Liberu\Billing\Core\Filament\Resources\BillingTaxProfileResource\Pages\EditBillingTaxProfile;
use Liberu\Billing\Core\Filament\Resources\BillingTaxProfileResource\Pages\ListBillingTaxProfiles;
use Liberu\Billing\Core\Models\BillingTaxProfile;

final class BillingTaxProfileResource extends BillingCoreResource
{
    protected static ?string $model = BillingTaxProfile::class;

    public static function getPages(): array
    {
        return ['index' => ListBillingTaxProfiles::route('/'), 'create' => CreateBillingTaxProfile::route('/create'), 'edit' => EditBillingTaxProfile::route('/{record}/edit')];
    }
}
