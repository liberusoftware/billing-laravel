<?php

declare(strict_types=1);

namespace Liberu\Billing\Communications\Filament\Resources;

use Filament\Actions\Action;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Gate;
use Liberu\Billing\Communications\Actions\ProvisionVoipAccount;
use Liberu\Billing\Communications\Filament\Concerns\ScopesCurrentTeam;
use Liberu\Billing\Communications\Filament\Resources\VoipAccountResource\Pages\CreateVoipAccount;
use Liberu\Billing\Communications\Filament\Resources\VoipAccountResource\Pages\ListVoipAccounts;
use Liberu\Billing\Communications\Models\VoipAccount;

final class VoipAccountResource extends Resource
{
    protected static string|\UnitEnum|null $navigationGroup = 'Service Delivery';

    use ScopesCurrentTeam;

    protected static ?string $model = VoipAccount::class;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('customer_id')->required()->numeric(),
            TextInput::make('platform')->required()->maxLength(100),
            TextInput::make('sip_username')->required()->maxLength(255),
            TextInput::make('sip_secret')->required()->password()->maxLength(1000),
            TextInput::make('caller_id')->maxLength(255),
            TextInput::make('credit_limit')->numeric()->minValue(0),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('sip_username')->searchable(),
            TextColumn::make('platform')->badge(),
            TextColumn::make('status')->badge(),
            TextColumn::make('current_usage_cost')->money('USD'),
        ])->actions([
            Action::make('provision')->requiresConfirmation()->action(function (VoipAccount $record): void {
                Gate::authorize('update', $record);
                app(ProvisionVoipAccount::class)->handle($record);
            }),
        ])->defaultSort('id', 'desc');
    }

    public static function getPages(): array
    {
        return ['index' => ListVoipAccounts::route('/'), 'create' => CreateVoipAccount::route('/create')];
    }
}
