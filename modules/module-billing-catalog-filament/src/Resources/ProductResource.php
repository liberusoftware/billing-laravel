<?php

declare(strict_types=1);

namespace Liberu\Billing\Catalog\Filament\Resources;

use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Gate;
use Liberu\Billing\Catalog\Actions\TransitionProductLifecycle;
use Liberu\Billing\Catalog\Enums\ProductStatus;
use Liberu\Billing\Catalog\Filament\Concerns\ScopesCurrentTeam;
use Liberu\Billing\Catalog\Filament\Resources\ProductResource\Pages\CreateProduct;
use Liberu\Billing\Catalog\Filament\Resources\ProductResource\Pages\ListProducts;
use Liberu\Billing\Catalog\Models\Product;

final class ProductResource extends Resource
{
    use ScopesCurrentTeam;

    protected static ?string $model = Product::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-cube';

    protected static string|\UnitEnum|null $navigationGroup = 'Catalog & Pricing';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('name')->required()->maxLength(255),
            TextInput::make('sku')->required()->maxLength(100),
            TextInput::make('base_price_minor')->required()->integer()->minValue(0),
            TextInput::make('currency')->required()->length(3)->alpha()->default('USD'),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('name')->searchable()->sortable(), TextColumn::make('sku')->searchable(),
            TextColumn::make('base_price_minor'), TextColumn::make('currency')->badge(), TextColumn::make('status')->badge(),
        ])->actions([
            Action::make('lifecycle')->label('Update lifecycle')->form([Select::make('status')->options(['draft' => 'Draft', 'active' => 'Active', 'archived' => 'Archived'])->required()])->action(function (Product $record, array $data): void {
                Gate::authorize('update', $record);
                app(TransitionProductLifecycle::class)->execute($record, ProductStatus::from($data['status']));
            }),
        ])->defaultSort('id', 'desc');
    }

    public static function getPages(): array
    {
        return ['index' => ListProducts::route('/'), 'create' => CreateProduct::route('/create')];
    }
}
