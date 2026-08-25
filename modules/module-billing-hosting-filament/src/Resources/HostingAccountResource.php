<?php

declare(strict_types=1);

namespace Liberu\Billing\Hosting\Filament\Resources;

use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Gate;
use Liberu\Billing\Hosting\Actions\TransitionHostingAccount;
use Liberu\Billing\Hosting\Filament\Resources\HostingAccountResource\Pages\CreateHostingAccount;
use Liberu\Billing\Hosting\Filament\Resources\HostingAccountResource\Pages\ListHostingAccounts;
use Liberu\Billing\Hosting\Models\HostingAccount;

final class HostingAccountResource extends Resource
{
    protected static ?string $model = HostingAccount::class;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([TextInput::make('name')->required()->maxLength(255), TextInput::make('status')->required()->default('active')->maxLength(32)]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([TextColumn::make('name')->searchable(), TextColumn::make('status')->badge(), TextColumn::make('created_at')->dateTime()])->actions([
            Action::make('transition')->label('Update status')->form([Select::make('status')->options(['pending' => 'Pending', 'active' => 'Active', 'suspended' => 'Suspended', 'cancelled' => 'Cancelled', 'failed' => 'Failed'])->required()])->action(function (HostingAccount $record, array $data): void {
                Gate::authorize('update', $record);
                app(TransitionHostingAccount::class)->handle($record, $data['status']);
            }),
        ])->defaultSort('id', 'desc');
    }

    public static function getPages(): array
    {
        return ['index' => ListHostingAccounts::route('/'), 'create' => CreateHostingAccount::route('/create')];
    }
}
