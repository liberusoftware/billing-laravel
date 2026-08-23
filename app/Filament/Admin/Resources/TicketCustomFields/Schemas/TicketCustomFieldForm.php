<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\TicketCustomFields\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;

class TicketCustomFieldForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('label')
                    ->required()
                    ->maxLength(255),
                Select::make('type')
                    ->options([
                        'text' => 'Text',
                        'select' => 'Select',
                        'number' => 'Number',
                        'checkbox' => 'Checkbox',
                    ])
                    ->default('text')
                    ->live()
                    ->required(),
                Select::make('department_id')
                    ->relationship('department', 'name')
                    ->label('Department')
                    ->helperText('Leave empty to apply to all departments.'),
                TagsInput::make('options')
                    ->helperText('Choices for a select field.')
                    ->visible(fn (Get $get): bool => $get('type') === 'select'),
                Toggle::make('is_required'),
                Toggle::make('is_active')
                    ->default(true),
            ]);
    }
}
