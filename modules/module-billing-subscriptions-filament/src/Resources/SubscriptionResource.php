<?php

declare(strict_types=1);

namespace Liberu\Billing\Subscriptions\Filament\Resources;

use BackedEnum;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Liberu\Billing\Subscriptions\Filament\Resources\SubscriptionResource\Pages\CreateSubscription;
use Liberu\Billing\Subscriptions\Filament\Resources\SubscriptionResource\Pages\ListSubscriptions;
use Liberu\Billing\Subscriptions\Models\Subscription;

final class SubscriptionResource extends Resource
{
    protected static ?string $model = Subscription::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-arrow-path';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('customer_id')->integer()->minValue(1),
            TextInput::make('pricing_plan_id')->integer()->minValue(1),
            TextInput::make('trial_days')->integer()->minValue(0)->default(0),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('id')->sortable(),
            TextColumn::make('status')->badge(),
            TextColumn::make('pricing_plan_id')->label('Plan')->sortable(),
            TextColumn::make('starts_at')->dateTime()->sortable(),
            TextColumn::make('current_period_ends_at')->dateTime()->sortable(),
            TextColumn::make('auto_renew')->boolean(),
        ])->defaultSort('id', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => ListSubscriptions::route('/'),
            'create' => CreateSubscription::route('/create'),
        ];
    }
}
