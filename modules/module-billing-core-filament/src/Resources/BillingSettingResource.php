<?php

declare(strict_types=1);

namespace Liberu\Billing\Core\Filament\Resources;

use Liberu\Billing\Core\Filament\Resources\BillingSettingResource\Pages\CreateBillingSetting;
use Liberu\Billing\Core\Filament\Resources\BillingSettingResource\Pages\EditBillingSetting;
use Liberu\Billing\Core\Filament\Resources\BillingSettingResource\Pages\ListBillingSettings;
use Liberu\Billing\Core\Models\BillingSetting;

final class BillingSettingResource extends BillingCoreResource
{
    protected static string|\UnitEnum|null $navigationGroup = 'Billing Operations';

    protected static ?string $model = BillingSetting::class;

    public static function getPages(): array
    {
        return ['index' => ListBillingSettings::route('/'), 'create' => CreateBillingSetting::route('/create'), 'edit' => EditBillingSetting::route('/{record}/edit')];
    }
}
