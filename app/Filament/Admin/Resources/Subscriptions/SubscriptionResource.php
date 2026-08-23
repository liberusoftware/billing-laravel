<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\Subscriptions;

use App\Filament\Resources\SubscriptionResource\Pages;
use App\Models\Subscription;
use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Override;

class SubscriptionResource extends Resource
{
    #[Override]
    protected static ?string $model = Subscription::class;

    #[Override]
    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-collection';

    #[Override]
    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components(
                [
                    Select::make('customer_id')
                        ->relationship(
                            'customer',
                            'name'
                        )
                        ->required(),
                    Select::make('subscription_plan_id')
                        ->relationship(
                            'subscriptionPlan',
                            'name'
                        )
                        ->required(),
                    DatePicker::make('start_date')
                        ->required(),
                    DatePicker::make('end_date')
                        ->required(),
                    Select::make('status')
                        ->options(
                            [
                                'active' => 'Active',
                                'pending' => 'Pending',
                                'cancelled' => 'Cancelled',
                                'suspended' => 'Suspended',
                            ]
                        )
                        ->required(),
                    Toggle::make('auto_renew')
                        ->required(),
                ]
            );
    }

    #[Override]
    public static function table(Table $table): Table
    {
        return $table
            ->columns(
                [
                    TextColumn::make('customer.name'),
                    TextColumn::make('subscriptionPlan.name'),
                    TextColumn::make('status'),
                    TextColumn::make('start_date'),
                    TextColumn::make('end_date'),
                    IconColumn::make('auto_renew')
                        ->boolean(),
                ]
            )
            ->filters(
                [
                    SelectFilter::make('status')
                        ->options(
                            [
                                'active' => 'Active',
                                'pending' => 'Pending',
                                'cancelled' => 'Cancelled',
                                'suspended' => 'Suspended',
                            ]
                        ),
                ]
            )
            ->recordActions(
                [
                    EditAction::make(),
                    DeleteAction::make(),
                ]
            );
    }

    #[Override]
    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    #[Override]
    public static function getPages(): array
    {
        return [
            // 'index' => Pages\ListSubscriptions::route('/'),
            // 'create' => Pages\CreateSubscription::route('/create'),
            // 'edit' => Pages\EditSubscription::route('/{record}/edit'),
        ];
    }
}
