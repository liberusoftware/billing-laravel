<?php

declare(strict_types=1);

namespace Liberu\Billing\Core\Filament\Resources;

use Liberu\Billing\Core\Filament\Resources\BillingTermResource\Pages\CreateBillingTerm;
use Liberu\Billing\Core\Filament\Resources\BillingTermResource\Pages\EditBillingTerm;
use Liberu\Billing\Core\Filament\Resources\BillingTermResource\Pages\ListBillingTerms;
use Liberu\Billing\Core\Models\BillingTerm;

final class BillingTermResource extends BillingCoreResource
{
    protected static ?string $model = BillingTerm::class;

    public static function getPages(): array
    {
        return ['index' => ListBillingTerms::route('/'), 'create' => CreateBillingTerm::route('/create'), 'edit' => EditBillingTerm::route('/{record}/edit')];
    }
}
