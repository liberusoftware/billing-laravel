<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\TicketDepartments\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class TicketDepartmentForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->required()
                    ->maxLength(255),
                TextInput::make('email')
                    ->email()
                    ->maxLength(255)
                    ->helperText('Inbound address used for email piping into this department.'),
                Toggle::make('is_active')
                    ->default(true),
            ]);
    }
}
