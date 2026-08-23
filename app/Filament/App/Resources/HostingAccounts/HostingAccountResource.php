<?php

declare(strict_types=1);

namespace App\Filament\App\Resources\HostingAccounts;

use App\Filament\App\Resources\HostingAccounts\Pages\CreateHostingAccount;
use App\Filament\App\Resources\HostingAccounts\Pages\EditHostingAccount;
use App\Filament\App\Resources\HostingAccounts\Pages\ListHostingAccounts;
use App\Models\HostingAccount;
use BackedEnum;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Override;

class HostingAccountResource extends Resource
{
    #[Override]
    protected static ?string $model = HostingAccount::class;

    #[Override]
    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-server';

    #[Override]
    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components(
                [
                    Select::make('customer_id')
                        ->relationship(
                            'customer',
                            'name'
                        )
                        ->required(),
                    Select::make('subscription_id')
                        ->relationship(
                            'subscription',
                            'id'
                        )
                        ->required(),
                    Select::make('control_panel')
                        ->options(
                            [
                                'cpanel' => 'cPanel',
                                'plesk' => 'Plesk',
                                'directadmin' => 'DirectAdmin',
                            ]
                        )
                        ->required(),
                    TextInput::make('username')
                        ->required()
                        ->maxLength(255),
                    TextInput::make('domain')
                        ->required()
                        ->maxLength(255),
                    TextInput::make('package')
                        ->required()
                        ->maxLength(255),
                    Select::make('status')
                        ->options(
                            [
                                'active' => 'Active',
                                'suspended' => 'Suspended',
                            ]
                        )
                        ->required(),
                ]
            );
    }

    #[Override]
    public static function table(Table $table): Table
    {
        return $table
            ->columns(
                [
                    TextColumn::make('customer.name'),
                    TextColumn::make('subscription.id'),
                    TextColumn::make('control_panel'),
                    TextColumn::make('username'),
                    TextColumn::make('domain'),
                    TextColumn::make('package'),
                    TextColumn::make('status'),
                ]
            )
            ->filters(
                [
                    //
                ]
            )
            ->recordActions(
                [
                    EditAction::make(),
                ]
            )
            ->toolbarActions(
                [
                    DeleteBulkAction::make(),
                ]
            );
    }

    #[Override]
    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    #[Override]
    public static function getPages(): array
    {
        return [
            'index' => ListHostingAccounts::route('/'),
            'create' => CreateHostingAccount::route('/create'),
            'edit' => EditHostingAccount::route('/{record}/edit'),
        ];
    }
}
