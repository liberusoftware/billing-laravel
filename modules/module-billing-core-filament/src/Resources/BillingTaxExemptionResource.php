<?php

declare(strict_types=1);

namespace Liberu\Billing\Core\Filament\Resources;

use Liberu\Billing\Core\Filament\Resources\BillingTaxExemptionResource\Pages\CreateBillingTaxExemption;
use Liberu\Billing\Core\Filament\Resources\BillingTaxExemptionResource\Pages\EditBillingTaxExemption;
use Liberu\Billing\Core\Filament\Resources\BillingTaxExemptionResource\Pages\ListBillingTaxExemptions;
use Liberu\Billing\Core\Models\BillingTaxExemption;

final class BillingTaxExemptionResource extends BillingCoreResource
{
    protected static string|\UnitEnum|null $navigationGroup = 'Billing Operations';

    protected static ?string $model = BillingTaxExemption::class;

    public static function getPages(): array
    {
        return ['index' => ListBillingTaxExemptions::route('/'), 'create' => CreateBillingTaxExemption::route('/create'), 'edit' => EditBillingTaxExemption::route('/{record}/edit')];
    }
}
