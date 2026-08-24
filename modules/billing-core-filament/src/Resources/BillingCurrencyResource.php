<?php

declare(strict_types=1);

namespace Liberu\Billing\Core\Filament\Resources;

use Liberu\Billing\Core\Filament\Resources\BillingCurrencyResource\Pages\CreateBillingCurrency;
use Liberu\Billing\Core\Filament\Resources\BillingCurrencyResource\Pages\EditBillingCurrency;
use Liberu\Billing\Core\Filament\Resources\BillingCurrencyResource\Pages\ListBillingCurrencies;
use Liberu\Billing\Core\Models\BillingCurrency;

final class BillingCurrencyResource extends BillingCoreResource
{
    protected static ?string $model = BillingCurrency::class;

    public static function getPages(): array
    {
        return ['index' => ListBillingCurrencies::route('/'), 'create' => CreateBillingCurrency::route('/create'), 'edit' => EditBillingCurrency::route('/{record}/edit')];
    }
}
