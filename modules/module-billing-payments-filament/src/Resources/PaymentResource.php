<?php

declare(strict_types=1);

namespace Liberu\Billing\Payments\Filament\Resources;

use BackedEnum;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Liberu\Billing\Payments\Filament\Resources\PaymentResource\Pages\CreatePayment;
use Liberu\Billing\Payments\Filament\Resources\PaymentResource\Pages\ListPayments;
use Liberu\Billing\Payments\Models\Payment;

final class PaymentResource extends Resource
{
    protected static ?string $model = Payment::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-banknotes';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('amount_minor')->required()->integer()->minValue(1),
            TextInput::make('currency')->required()->length(3)->alpha()->default('USD'),
            TextInput::make('gateway')->maxLength(100),
            TextInput::make('payment_method')->maxLength(100),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('id')->sortable(),
            TextColumn::make('amount_minor')->sortable(),
            TextColumn::make('currency')->badge(),
            TextColumn::make('status')->badge(),
            TextColumn::make('gateway'),
            TextColumn::make('created_at')->dateTime()->sortable(),
        ])->defaultSort('id', 'desc');
    }

    public static function getPages(): array
    {
        return ['index' => ListPayments::route('/'), 'create' => CreatePayment::route('/create')];
    }
}
