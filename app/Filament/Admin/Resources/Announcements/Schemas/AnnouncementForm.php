<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\Announcements\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class AnnouncementForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components(
                [
                    TextInput::make('title')
                        ->required()
                        ->maxLength(255),
                    Textarea::make('body')
                        ->required()
                        ->columnSpanFull(),
                    Select::make('type')
                        ->options([
                            'announcement' => 'Announcement',
                            'network_status' => 'Network status',
                        ])
                        ->default('announcement')
                        ->required(),
                    Toggle::make('is_published'),
                    DateTimePicker::make('published_at'),
                    DateTimePicker::make('starts_at'),
                    DateTimePicker::make('ends_at'),
                ]
            );
    }
}
