<?php

declare(strict_types=1);

namespace Liberu\Billing\Payments\Filament\Resources;

use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Liberu\Billing\Payments\Filament\Concerns\ScopesCurrentTeam;
use Liberu\Billing\Payments\Filament\Resources\PaymentMethodResource\Pages\CreatePaymentMethod;
use Liberu\Billing\Payments\Filament\Resources\PaymentMethodResource\Pages\ListPaymentMethods;
use Liberu\Billing\Payments\Models\PaymentMethod;

final class PaymentMethodResource extends Resource
{
    use ScopesCurrentTeam;

    protected static ?string $model = PaymentMethod::class;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([TextInput::make('type')->required()->maxLength(64), TextInput::make('provider')->required()->maxLength(64), TextInput::make('display_name')->maxLength(255), TextInput::make('last_four')->length(4)]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([TextColumn::make('type')->badge(), TextColumn::make('provider'), TextColumn::make('status')->badge()]);
    }

    public static function getPages(): array
    {
        return ['index' => ListPaymentMethods::route('/'), 'create' => CreatePaymentMethod::route('/create')];
    }
}
