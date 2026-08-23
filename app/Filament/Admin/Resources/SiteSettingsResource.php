<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\SiteSettingsResource\Pages\CreateSiteSettings;
use App\Filament\Admin\Resources\SiteSettingsResource\Pages\EditSiteSettings;
use App\Filament\Admin\Resources\SiteSettingsResource\Pages\ListSiteSettings;
use App\Models\SiteSettings;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Override;
use UnitEnum;

class SiteSettingsResource extends Resource
{
    #[Override]
    protected static ?string $model = SiteSettings::class;

    // Site settings are global, not team-owned; opt out of the panel's tenant scoping.
    protected static bool $isScopedToTenant = false;

    #[Override]
    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-cog-8-tooth';

    #[Override]
    protected static string|UnitEnum|null $navigationGroup = 'Settings';

    #[Override]
    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components(
                [
                    TextInput::make('name')
                        ->required()
                        ->maxLength(255),
                    TextInput::make('email')
                        ->email()
                        ->maxLength(255),
                    TextInput::make('currency')
                        ->maxLength(10),
                    TextInput::make('default_language')
                        ->maxLength(10)
                        ->default('en'),
                    TextInput::make('address')
                        ->maxLength(255),
                    TextInput::make('country')
                        ->maxLength(255),
                    TextInput::make('phone_01')
                        ->tel()
                        ->maxLength(20),
                    TextInput::make('phone_02')
                        ->tel()
                        ->maxLength(20),
                    TextInput::make('phone_03')
                        ->tel()
                        ->maxLength(20),
                    TextInput::make('facebook')
                        ->url()
                        ->maxLength(255),
                    TextInput::make('twitter')
                        ->url()
                        ->maxLength(255),
                    TextInput::make('github')
                        ->url()
                        ->maxLength(255),
                    TextInput::make('youtube')
                        ->url()
                        ->maxLength(255),
                ]
            );
    }

    #[Override]
    public static function table(Table $table): Table
    {
        return $table
            ->columns(
                [
                    TextColumn::make('name')
                        ->searchable()
                        ->sortable(),
                    TextColumn::make('email'),
                    TextColumn::make('currency'),
                    TextColumn::make('default_language'),
                ]
            )
            ->filters([])
            ->recordActions(
                [
                    EditAction::make(),
                    DeleteAction::make(),
                ]
            )
            ->toolbarActions(
                [
                    BulkActionGroup::make(
                        [
                            DeleteBulkAction::make(),
                        ]
                    ),
                ]
            );
    }

    #[Override]
    public static function getRelations(): array
    {
        return [];
    }

    #[Override]
    public static function getPages(): array
    {
        return [
            'index' => ListSiteSettings::route('/'),
            'create' => CreateSiteSettings::route('/create'),
            'edit' => EditSiteSettings::route('/{record}/edit'),
        ];
    }
}
