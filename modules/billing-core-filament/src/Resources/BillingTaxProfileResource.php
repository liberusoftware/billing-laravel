<?php

declare(strict_types=1);

namespace Liberu\Billing\Core\Filament\Resources;

use Filament\Actions\Action;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Gate;
use Liberu\Billing\Core\Actions\CalculateTax;
use Liberu\Billing\Core\Filament\Resources\BillingTaxProfileResource\Pages\CreateBillingTaxProfile;
use Liberu\Billing\Core\Filament\Resources\BillingTaxProfileResource\Pages\EditBillingTaxProfile;
use Liberu\Billing\Core\Filament\Resources\BillingTaxProfileResource\Pages\ListBillingTaxProfiles;
use Liberu\Billing\Core\Models\BillingTaxProfile;

final class BillingTaxProfileResource extends BillingCoreResource
{
    protected static ?string $model = BillingTaxProfile::class;

    public static function table(Table $table): Table
    {
        return parent::table($table)->actions([
            Action::make('calculate')->label('Calculate tax')->form([
                TextInput::make('amount')->required()->numeric()->minValue(0),
            ])->action(function (BillingTaxProfile $record, array $data): void {
                Gate::authorize('view', $record);
                $teamId = (int) (auth()->user()->current_team_id ?? auth()->user()->currentTeam->id);
                $result = app(CalculateTax::class)->execute($teamId, (float) $data['amount'], $record->jurisdiction);
                Notification::make()->title(sprintf('%s tax / %s total', $result['tax'], $result['total']))->success()->send();
            }),
        ]);
    }

    public static function getPages(): array
    {
        return ['index' => ListBillingTaxProfiles::route('/'), 'create' => CreateBillingTaxProfile::route('/create'), 'edit' => EditBillingTaxProfile::route('/{record}/edit')];
    }
}
