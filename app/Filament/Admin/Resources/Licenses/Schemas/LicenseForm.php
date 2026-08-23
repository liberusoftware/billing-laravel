<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\Licenses\Schemas;

use App\Enums\LicenseStatus;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class LicenseForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components(
                [
                    Select::make('customer_id')
                        ->relationship('customer', 'name')
                        ->searchable()
                        ->preload()
                        ->required(),
                    Select::make('product_service_id')
                        ->relationship('productService', 'name')
                        ->searchable()
                        ->preload(),
                    Select::make('status')
                        ->options(LicenseStatus::class)
                        ->default(LicenseStatus::Active)
                        ->required(),
                    TextInput::make('max_instances')
                        ->integer()
                        ->default(1)
                        ->required(),
                    DatePicker::make('valid_until'),
                    Textarea::make('notes')
                        ->columnSpanFull(),
                ]
            );
    }
}
