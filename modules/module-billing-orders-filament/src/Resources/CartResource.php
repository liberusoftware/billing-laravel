<?php

declare(strict_types=1);

namespace Liberu\Billing\Orders\Filament\Resources;

use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Gate;
use Liberu\Billing\Orders\Actions\CheckoutCart;
use Liberu\Billing\Orders\Filament\Concerns\ScopesCurrentTeam;
use Liberu\Billing\Orders\Filament\Resources\CartResource\Pages\CreateCart;
use Liberu\Billing\Orders\Filament\Resources\CartResource\Pages\ListCarts;
use Liberu\Billing\Orders\Models\Cart;

final class CartResource extends Resource
{
    protected static string|\UnitEnum|null $navigationGroup = 'Customers & Sales';

    use ScopesCurrentTeam;

    protected static ?string $model = Cart::class;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('customer_id')->integer()->minValue(1),
            TextInput::make('currency')->required()->length(3)->default('USD'),
            Textarea::make('items')->required()->default('[]')->helperText('JSON array'),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([TextColumn::make('customer_id'), TextColumn::make('currency'), TextColumn::make('status')->badge(), TextColumn::make('expires_at')->dateTime()])->actions([
            Action::make('checkout')->form([TextInput::make('subtotal_minor')->required()->integer()->minValue(0)])->visible(fn (Cart $record): bool => $record->getAttribute('status') === 'open')->action(function (Cart $record, array $data, CheckoutCart $checkout): void {
                Gate::authorize('update', $record);
                $checkout->execute($record, ['subtotal_minor' => (int) $data['subtotal_minor']]);
            }),
        ]);
    }

    public static function getPages(): array
    {
        return ['index' => ListCarts::route('/'), 'create' => CreateCart::route('/create')];
    }
}
