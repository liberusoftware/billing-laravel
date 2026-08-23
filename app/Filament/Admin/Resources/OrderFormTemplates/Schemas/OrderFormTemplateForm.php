<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\OrderFormTemplates\Schemas;

use App\Models\SubscriptionPlan;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class OrderFormTemplateForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components(
                [
                    TextInput::make('name')
                        ->required()
                        ->maxLength(255),
                    TextInput::make('slug')
                        ->required()
                        ->maxLength(255),
                    Textarea::make('description')
                        ->columnSpanFull(),
                    Toggle::make('is_active')
                        ->default(true),
                    Select::make('config.plan_ids')
                        ->label('Offered plans')
                        ->multiple()
                        ->options(fn (): array => SubscriptionPlan::query()
                            ->where('is_active', true)
                            ->pluck('name', 'id')
                            ->all()),
                ]
            );
    }
}
