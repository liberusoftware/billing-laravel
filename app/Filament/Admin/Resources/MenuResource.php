<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\MenuResource\Pages\CreateMenu;
use App\Filament\Admin\Resources\MenuResource\Pages\EditMenu;
use App\Filament\Admin\Resources\MenuResource\Pages\ListMenus;
use App\Models\Menu;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Override;
use UnitEnum;

class MenuResource extends Resource
{
    #[Override]
    protected static ?string $model = Menu::class;

    // Menus are global, not team-owned; opt out of the panel's tenant scoping.
    protected static bool $isScopedToTenant = false;

    #[Override]
    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-bars-3';

    #[Override]
    protected static string|UnitEnum|null $navigationGroup = 'Administration';

    #[Override]
    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components(
                [
                    TextInput::make('name')
                        ->required()
                        ->maxLength(255),
                    TextInput::make('url')
                        ->url()
                        ->required()
                        ->maxLength(255),
                    Select::make('parent_id')
                        ->label('Parent Menu')
                        ->relationship(
                            'parent',
                            'name'
                        )
                        ->nullable(),
                    TextInput::make('order')
                        ->numeric()
                        ->default(0),
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
                    TextColumn::make('url')
                        ->limit(40),
                    TextColumn::make('parent.name')
                        ->label('Parent')
                        ->placeholder('—'),
                    TextColumn::make('order')
                        ->sortable(),
                ]
            )
            ->filters([])
            ->recordActions(
                [
                    EditAction::make(),
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
            'index' => ListMenus::route('/'),
            'create' => CreateMenu::route('/create'),
            'edit' => EditMenu::route('/{record}/edit'),
        ];
    }
}
