<?php

namespace App\Filament\Components;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;

class PaymentForm extends Component
{
    public static function make(): Section
    {
        return Section::make()
            ->schema(
                [
                    Select::make('payment_method')
                        ->options(
                            [
                                'stripe' => 'Credit Card (Stripe)',
                                'paypal' => 'PayPal',
                            ]
                        )
                        ->required()
                        ->reactive(),

                    TextInput::make('card_number')
                        ->label('Card Number')
                        ->numeric()
                        ->maxLength(16)
                        ->hidden(fn ($get): bool => $get('payment_method') !== 'stripe'),

                    Grid::make(3)
                        ->schema(
                            [
                                TextInput::make('expiry_month')
                                    ->label('Month')
                                    ->numeric()
                                    ->maxLength(2),
                                TextInput::make('expiry_year')
                                    ->label('Year')
                                    ->numeric()
                                    ->maxLength(4),
                                TextInput::make('cvv')
                                    ->label('CVV')
                                    ->numeric()
                                    ->maxLength(4),
                            ]
                        )
                        ->hidden(fn ($get): bool => $get('payment_method') !== 'stripe'),
                ]
            );
    }
}
