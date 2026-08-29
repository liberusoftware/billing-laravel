<?php

declare(strict_types=1);

namespace Liberu\Billing\Core\Filament\Resources;

use Filament\Actions\Action;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Gate;
use Liberu\Billing\Core\Actions\ConvertCurrency;
use Liberu\Billing\Core\Filament\Resources\BillingCurrencyResource\Pages\CreateBillingCurrency;
use Liberu\Billing\Core\Filament\Resources\BillingCurrencyResource\Pages\EditBillingCurrency;
use Liberu\Billing\Core\Filament\Resources\BillingCurrencyResource\Pages\ListBillingCurrencies;
use Liberu\Billing\Core\Models\BillingCurrency;

final class BillingCurrencyResource extends BillingCoreResource
{
    protected static ?string $model = BillingCurrency::class;

    public static function table(Table $table): Table
    {
        return parent::table($table)->actions([
            Action::make('convert')->label('Convert amount')->form([
                TextInput::make('amount')->required()->numeric(),
                TextInput::make('to')->required()->length(3)->alpha()->default('USD'),
            ])->action(function (BillingCurrency $record, array $data): void {
                Gate::authorize('view', $record);
                $teamId = (int) (auth()->user()->current_team_id ?? auth()->user()->currentTeam->id);
                $result = app(ConvertCurrency::class)->execute($teamId, (float) $data['amount'], (string) $record->code, (string) $data['to']);
                Notification::make()->title(sprintf('%s %s', $result['amount'], $result['to']))->success()->send();
            }),
        ]);
    }

    public static function getPages(): array
    {
        return ['index' => ListBillingCurrencies::route('/'), 'create' => CreateBillingCurrency::route('/create'), 'edit' => EditBillingCurrency::route('/{record}/edit')];
    }
}
