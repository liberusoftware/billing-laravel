<?php

namespace App\Filament\Admin\Resources\HostingServers;

use Filament\Schemas\Schema;
use Filament\Schemas\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Toggle;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Actions\EditAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use App\Filament\Resources\HostingServerResource\Pages;
use App\Models\HostingServer;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Tables\Table;
use Filament\Tables;
use Illuminate\Database\Eloquent\Builder;

class HostingServerResource extends Resource
{
    // protected static ?string $model = HostingServer::class;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-server';
    protected static string | \UnitEnum | null $navigationGroup = 'Hosting';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make()
                    ->schema([
                        TextInput::make('name')
                            ->required()
                            ->maxLength(255),
                        TextInput::make('hostname')
                            ->required()
                            ->maxLength(255),
                        Select::make('control_panel')
                            ->options([
                                'cpanel' => 'cPanel',
                                'plesk' => 'Plesk',
                                'directadmin' => 'DirectAdmin',
                                'virtualmin' => 'Virtualmin',
                            ])
                            ->required(),
                        TextInput::make('api_token')
                            ->required()
                            ->password()
                            ->maxLength(255),
                        TextInput::make('api_url')
                            ->required()
                            ->maxLength(255),
                        Toggle::make('is_active')
                            ->default(true),
                        TextInput::make('max_accounts')
                            ->numeric()
                            ->default(0)
                            ->minValue(0),
                    ])
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->searchable(),
                TextColumn::make('hostname')
                    ->searchable(),
                BadgeColumn::make('control_panel')
                    ->colors([
                        'primary' => 'cpanel',
                        'success' => 'plesk',
                        'warning' => 'directadmin',
                        'danger' => 'virtualmin',
                    ]),
                IconColumn::make('is_active')
                    ->boolean(),
                TextColumn::make('active_accounts')
                    ->label('Active/Max Accounts')
                    ->formatStateUsing(fn ($record) => "{$record->active_accounts}/{$record->max_accounts}"),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('control_panel')
                    ->options([
                        'cpanel' => 'cPanel',
                        'plesk' => 'Plesk',
                        'directadmin' => 'DirectAdmin',
                        'virtualmin' => 'Virtualmin',
                    ]),
                TernaryFilter::make('is_active')
                    ->label('Active Status'),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                DeleteBulkAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            // 'index' => Pages\ListHostingServers::route('/'),
            // 'create' => Pages\CreateHostingServer::route('/create'),
            // 'edit' => Pages\EditHostingServer::route('/{record}/edit'),
        ];
    }
}