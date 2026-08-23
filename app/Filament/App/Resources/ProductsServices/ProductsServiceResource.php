<?php

namespace App\Filament\App\Resources\ProductsServices;

use App\Filament\App\Resources\ProductsServices\Pages\CreateProductsService;
use App\Filament\App\Resources\ProductsServices\Pages\EditProductsService;
use App\Filament\App\Resources\ProductsServices\Pages\ListProductsServices;
use App\Models\Products_Service;
use App\Models\Tld;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Override;

class ProductsServiceResource extends Resource
{
    #[Override]
    protected static ?string $model = Products_Service::class;

    #[Override]
    protected static ?string $modelLabel = 'Products Service';

    #[Override]
    protected static ?string $pluralModelLabel = 'Products Services';

    #[Override]
    protected static ?string $navigationLabel = 'Products Services';

    #[Override]
    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-rectangle-stack';

    #[Override]
    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components(
                [
                    TextInput::make('name')
                        ->required()
                        ->maxLength(255),
                    Textarea::make('description')
                        ->maxLength(65535)
                        ->columnSpanFull(),
                    TextInput::make('base_price')
                        ->required()
                        ->numeric()
                        ->prefix('$'),
                    Select::make('type')
                        ->required()
                        ->options(
                            [
                                'hosting' => 'Hosting',
                                'domain' => 'Domain',
                                'addon' => 'Add-on',
                            ]
                        )
                        ->reactive(),
                    Select::make('pricing_model')
                        ->required()
                        ->options(
                            [
                                'fixed' => 'Fixed',
                                'tiered' => 'Tiered',
                                'usage_based' => 'Usage-based',
                            ]
                        )
                        ->reactive(),
                    KeyValue::make('custom_pricing_data')
                        ->keyLabel('Tier/Usage')
                        ->valueLabel('Price')
                        ->visible(
                            fn (Get $get): bool => in_array(
                                $get('pricing_model'),
                                [
                                    'tiered',
                                    'usage_based',
                                ]
                            )
                        )
                        ->columnSpanFull(),
                    Select::make('tld_id')
                        ->label('TLD')
                        ->options(
                            Tld::all()->pluck(
                                'name',
                                'id'
                            )
                        )
                        ->visible(fn (Get $get): bool => $get('type') === 'domain')
                        ->required(fn (Get $get): bool => $get('type') === 'domain'),
                    Select::make('markup_type')
                        ->label('Markup Type')
                        ->options(
                            [
                                'percentage' => 'Percentage',
                                'fixed' => 'Fixed Amount',
                            ]
                        )
                        ->visible(fn (Get $get): bool => $get('type') === 'domain')
                        ->required(fn (Get $get): bool => $get('type') === 'domain'),
                    TextInput::make('markup_value')
                        ->label('Markup Value')
                        ->numeric()
                        ->visible(fn (Get $get): bool => $get('type') === 'domain')
                        ->required(fn (Get $get): bool => $get('type') === 'domain'),
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
                        ->searchable(),
                    TextColumn::make('type'),
                    TextColumn::make('base_price')
                        ->money('USD')
                        ->sortable(),
                    TextColumn::make('pricing_model'),
                    TextColumn::make('tld.name')
                        ->label('TLD'),
                    // ->visible(fn ($record) => $record->type === 'domain'),
                    TextColumn::make('markup_type'),
                    // ->visible(fn ($record) => $record->type === 'domain'),
                    TextColumn::make('markup_value'),
                    // ->visible(fn ($record) => $record->type === 'domain'),
                    TextColumn::make('created_at')
                        ->dateTime()
                        ->sortable()
                        ->toggleable(isToggledHiddenByDefault: true),
                    TextColumn::make('updated_at')
                        ->dateTime()
                        ->sortable()
                        ->toggleable(isToggledHiddenByDefault: true),
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
                    BulkActionGroup::make(
                        [
                            DeleteBulkAction::make(),
                        ]
                    ),
                ]
            );
    }

    #[Override]
    public static function getPages(): array
    {
        return [
            'index' => ListProductsServices::route('/'),
            'create' => CreateProductsService::route('/create'),
            'edit' => EditProductsService::route('/{record}/edit'),
        ];
    }
}
