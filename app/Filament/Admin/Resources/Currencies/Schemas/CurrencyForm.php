<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\Currencies\Schemas;

use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class CurrencyForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components(
                [
                    TextInput::make('code')
                        ->required()
                        ->maxLength(3),
                    TextInput::make('name')
                        ->required()
                        ->maxLength(255),
                    TextInput::make('symbol')
                        ->maxLength(255),
                    // Repricing lever: super_admin only (it reprices every tenant's invoices/reports).
                    TextInput::make('exchange_rate')
                        ->required()
                        ->numeric()
                        ->helperText('Rate relative to the base currency.')
                        ->visible(fn (): bool => (bool) auth()->user()?->hasRole('super_admin')),
                    // Non-super_admins can't set a rate; force it to 1 (base-relative) at save
                    // time so the NOT NULL column is satisfied without exposing the lever.
                    Hidden::make('exchange_rate')
                        ->visible(fn (): bool => ! auth()->user()?->hasRole('super_admin'))
                        ->dehydrateStateUsing(fn (): int => 1),
                    TextInput::make('decimal_precision')
                        ->required()
                        ->integer()
                        ->default(2),
                    Toggle::make('is_enabled')
                        ->default(true),
                    Toggle::make('is_base')
                        ->helperText('Base currency for reports; exchange_rate should be 1.')
                        ->visible(fn (): bool => (bool) auth()->user()?->hasRole('super_admin')),
                ]
            );
    }
}
