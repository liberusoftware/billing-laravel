<?php

declare(strict_types=1);

namespace Liberu\Billing\Core\Filament\Resources;

use Liberu\Billing\Core\Filament\Resources\BillingSequenceResource\Pages\CreateBillingSequence;
use Liberu\Billing\Core\Filament\Resources\BillingSequenceResource\Pages\EditBillingSequence;
use Liberu\Billing\Core\Filament\Resources\BillingSequenceResource\Pages\ListBillingSequences;
use Liberu\Billing\Core\Models\BillingSequence;

final class BillingSequenceResource extends BillingCoreResource
{
    protected static string|\UnitEnum|null $navigationGroup = 'Billing Operations';

    protected static ?string $model = BillingSequence::class;

    public static function getPages(): array
    {
        return ['index' => ListBillingSequences::route('/'), 'create' => CreateBillingSequence::route('/create'), 'edit' => EditBillingSequence::route('/{record}/edit')];
    }
}
