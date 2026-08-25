<?php

declare(strict_types=1);

namespace Liberu\Billing\Domains\Filament\Resources;

use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Gate;
use Liberu\Billing\Domains\Actions\RedeemDomain;
use Liberu\Billing\Domains\Actions\RegisterDomain;
use Liberu\Billing\Domains\Actions\RenewDomain;
use Liberu\Billing\Domains\Actions\TransferDomain;
use Liberu\Billing\Domains\Filament\Concerns\ScopesCurrentTeam;
use Liberu\Billing\Domains\Filament\Resources\DomainResource\Pages\CreateDomain as CreateDomainPage;
use Liberu\Billing\Domains\Filament\Resources\DomainResource\Pages\ListDomains;
use Liberu\Billing\Domains\Models\Domain;

final class DomainResource extends Resource
{
    use ScopesCurrentTeam;

    protected static ?string $model = Domain::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-globe-alt';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('name')->required()->maxLength(255),
            TextInput::make('status')->required()->maxLength(50)->default('active'),
            TextInput::make('registrar')->maxLength(100),
            TextInput::make('transfer_status')->maxLength(50),
            TextInput::make('expires_at')->type('datetime-local'),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('name')->searchable()->sortable(),
            TextColumn::make('status')->badge(),
            TextColumn::make('registrar'),
            TextColumn::make('expires_at')->dateTime()->sortable(),
        ])->actions([
            Action::make('register')->form([TextInput::make('customer_id')->required()])->visible(fn (Domain $record): bool => $record->getRawOriginal('status') !== 'registered')->action(function (Domain $record, array $data): void {
                Gate::authorize('update', $record);
                app(RegisterDomain::class)->execute($record, $data['customer_id']);
            }),
            Action::make('renew')->form([TextInput::make('period')->integer()->minValue(1)->maxValue(10)->default(1)->required()])->visible(fn (Domain $record): bool => $record->getRawOriginal('status') === 'registered')->action(function (Domain $record, array $data): void {
                Gate::authorize('update', $record);
                app(RenewDomain::class)->execute($record, (int) $data['period']);
            }),
            Action::make('transfer')->form([TextInput::make('auth_code')->required()->maxLength(255), TextInput::make('customer_id')->required(), TextInput::make('registrar')->maxLength(50)])->action(function (Domain $record, array $data): void {
                Gate::authorize('update', $record);
                app(TransferDomain::class)->execute($record, $data['auth_code'], $data['customer_id'], $data['registrar'] ?? null);
            }),
            Action::make('redeem')->requiresConfirmation()->visible(fn (Domain $record): bool => $record->getRawOriginal('status') === 'expired')->action(function (Domain $record): void {
                Gate::authorize('update', $record);
                app(RedeemDomain::class)->execute($record);
            }),
        ])->defaultSort('id', 'desc');
    }

    public static function getPages(): array
    {
        return ['index' => ListDomains::route('/'), 'create' => CreateDomainPage::route('/create')];
    }
}
