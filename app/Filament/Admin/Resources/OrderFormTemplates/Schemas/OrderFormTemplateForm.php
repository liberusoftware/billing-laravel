<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\OrderFormTemplates\Schemas;

use App\Models\Products_Service;
use App\Models\SubscriptionPlan;
use Filament\Forms\Components\Repeater;
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
                    Repeater::make('config.hosting_packages')
                        ->label('Hosting package mappings')
                        ->schema([
                            Select::make('plan_id')->label('Plan')->required()
                                ->options(fn (): array => SubscriptionPlan::query()->pluck('name', 'id')->all())
                                ->disableOptionsWhenSelectedInSiblingRepeaterItems(),
                            Select::make('product_service_id')->label('Hosting product')->required()
                                ->options(fn (): array => Products_Service::query()->where('type', 'hosting')->pluck('name', 'id')->all()),
                        ])->defaultItems(0),
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
