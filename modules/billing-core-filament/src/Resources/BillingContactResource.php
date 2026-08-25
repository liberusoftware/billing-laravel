<?php

declare(strict_types=1);

namespace Liberu\Billing\Core\Filament\Resources;

use Liberu\Billing\Core\Filament\Resources\BillingContactResource\Pages\CreateBillingContact;
use Liberu\Billing\Core\Filament\Resources\BillingContactResource\Pages\EditBillingContact;
use Liberu\Billing\Core\Filament\Resources\BillingContactResource\Pages\ListBillingContacts;
use Liberu\Billing\Core\Models\BillingContact;

final class BillingContactResource extends BillingCoreResource
{
    protected static ?string $model = BillingContact::class;

    public static function getPages(): array
    {
        return ['index' => ListBillingContacts::route('/'), 'create' => CreateBillingContact::route('/create'), 'edit' => EditBillingContact::route('/{record}/edit')];
    }
}
