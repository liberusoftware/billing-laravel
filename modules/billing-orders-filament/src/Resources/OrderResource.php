<?php

declare(strict_types=1);

namespace Liberu\Billing\Orders\Filament\Resources;

use BackedEnum;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Liberu\Billing\Orders\Filament\Resources\OrderResource\Pages\CreateOrder;
use Liberu\Billing\Orders\Filament\Resources\OrderResource\Pages\ListOrders;
use Liberu\Billing\Orders\Models\Order;

final class OrderResource extends Resource
{
    protected static ?string $model = Order::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-shopping-cart';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([TextInput::make('currency')->required()->length(3)->default('USD'), TextInput::make('subtotal_minor')->required()->integer()->minValue(0), TextInput::make('discount_minor')->integer()->minValue(0), TextInput::make('tax_minor')->integer()->minValue(0)]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([TextColumn::make('order_number')->searchable(), TextColumn::make('currency')->badge(), TextColumn::make('total_minor'), TextColumn::make('status')->badge(), TextColumn::make('fraud_status')->badge()])->defaultSort('id', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => ListOrders::route('/'),
            'create' => CreateOrder::route('/create'),
        ];
    }
}
