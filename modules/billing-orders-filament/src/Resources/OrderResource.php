<?php

declare(strict_types=1);

namespace Liberu\Billing\Orders\Filament\Resources;

use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Gate;
use Liberu\Billing\Orders\Actions\AddChangeOrder;
use Liberu\Billing\Orders\Actions\ReviewFraud;
use Liberu\Billing\Orders\Actions\TransitionOrder;
use Liberu\Billing\Orders\Enums\FraudReviewStatus;
use Liberu\Billing\Orders\Enums\OrderStatus;
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
        return $table->columns([TextColumn::make('order_number')->searchable(), TextColumn::make('currency')->badge(), TextColumn::make('total_minor'), TextColumn::make('status')->badge(), TextColumn::make('fraud_status')->badge()])->actions([
            Action::make('review_fraud')->label('Review fraud')->form([Select::make('fraud_status')->options(collect(FraudReviewStatus::cases())->mapWithKeys(fn (FraudReviewStatus $status): array => [$status->value => ucfirst(str_replace('_', ' ', $status->value))])->all())->required()])->action(function (Order $record, array $data): void {
                Gate::authorize('update', $record);
                app(ReviewFraud::class)->execute($record, FraudReviewStatus::from($data['fraud_status']));
            }),
            Action::make('change_order')->label('Add change order')->form([TextInput::make('reason')->required()->maxLength(1000), TextInput::make('amount_minor')->integer()->minValue(0)->default(0)])->action(function (Order $record, array $data): void {
                Gate::authorize('update', $record);
                app(AddChangeOrder::class)->execute($record, $data);
            }),
            Action::make('transition')->label('Update status')->form([Select::make('status')->options(collect(OrderStatus::cases())->mapWithKeys(fn (OrderStatus $status): array => [$status->value => ucfirst(str_replace('_', ' ', $status->value))])->all())->required()])->action(function (Order $record, array $data, TransitionOrder $transition): void {
                Gate::authorize('update', $record);
                $transition->execute($record, OrderStatus::from($data['status']));
            }),
        ])->defaultSort('id', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => ListOrders::route('/'),
            'create' => CreateOrder::route('/create'),
        ];
    }
}
