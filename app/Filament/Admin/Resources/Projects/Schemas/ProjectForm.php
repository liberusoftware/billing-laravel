<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\Projects\Schemas;

use App\Enums\ProjectStatus;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class ProjectForm
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
                    TextInput::make('name')
                        ->required()
                        ->maxLength(255),
                    Select::make('status')
                        ->options(ProjectStatus::class)
                        ->default(ProjectStatus::Open->value)
                        ->required(),
                    DatePicker::make('due_date'),
                    Textarea::make('description')
                        ->columnSpanFull(),
                ]
            );
    }
}
