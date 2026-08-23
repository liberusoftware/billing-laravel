<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\PaymentGateways\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class PaymentGatewayForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components(
                [
                    TextInput::make('name')
                        ->required()
                        ->maxLength(255),
                    TextInput::make('api_key')
                        ->required()
                        ->maxLength(255),
                    TextInput::make('secret_key')
                        ->required()
                        ->password()
                        ->maxLength(255),
                    Toggle::make('is_active')
                        ->required(),
                ]
            );
    }
}
