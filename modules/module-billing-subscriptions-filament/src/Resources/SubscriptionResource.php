<?php

declare(strict_types=1);

namespace Liberu\Billing\Subscriptions\Filament\Resources;

use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Liberu\Billing\Subscriptions\Actions\CancelSubscription;
use Liberu\Billing\Subscriptions\Actions\ChangeSubscriptionPlan;
use Liberu\Billing\Subscriptions\Actions\ExpireSubscriptions;
use Liberu\Billing\Subscriptions\Actions\PauseSubscription;
use Liberu\Billing\Subscriptions\Actions\RenewSubscription;
use Liberu\Billing\Subscriptions\Actions\ResumeSubscription;
use Liberu\Billing\Subscriptions\Filament\Concerns\ScopesCurrentTeam;
use Liberu\Billing\Subscriptions\Filament\Resources\SubscriptionResource\Pages\CreateSubscription;
use Liberu\Billing\Subscriptions\Filament\Resources\SubscriptionResource\Pages\ListSubscriptions;
use Liberu\Billing\Subscriptions\Models\Subscription;

final class SubscriptionResource extends Resource
{
    use ScopesCurrentTeam;

    protected static ?string $model = Subscription::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-arrow-path';

    protected static string|\UnitEnum|null $navigationGroup = 'Billing Operations';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('customer_id')->integer()->minValue(1),
            TextInput::make('pricing_plan_id')->integer()->minValue(1),
            TextInput::make('trial_days')->integer()->minValue(0)->default(0),
            TextInput::make('period_days')->integer()->minValue(1)->maxValue(3660)->default(30),
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
            TextColumn::make('auto_renew')->badge(),
        ])->filters([
            SelectFilter::make('status')->options(['trialing' => 'Trialing', 'active' => 'Active', 'paused' => 'Paused', 'cancelled' => 'Cancelled', 'expired' => 'Expired']),
            SelectFilter::make('customer_id')->label('Customer ID')->options(fn (): array => Subscription::query()->whereNotNull('customer_id')->distinct()->pluck('customer_id', 'customer_id')->mapWithKeys(fn ($id): array => [(string) $id => (string) $id])->all()),
        ])->defaultSort('id', 'desc')->actions([
            Action::make('renew')
                ->label('Renew')
                ->form([TextInput::make('period_days')->integer()->minValue(1)->maxValue(3660)])
                ->visible(fn (Subscription $record): bool => ! in_array($record->getRawOriginal('status'), ['cancelled', 'expired'], true))
                ->action(fn (Subscription $record, array $data): Subscription => app(RenewSubscription::class)->execute($record, isset($data['period_days']) ? (int) $data['period_days'] : null)),
            Action::make('pause')
                ->label('Pause')
                ->visible(fn (Subscription $record): bool => ! in_array($record->getRawOriginal('status'), ['paused', 'cancelled', 'expired'], true))
                ->action(fn (Subscription $record): Subscription => app(PauseSubscription::class)->execute($record)),
            Action::make('resume')
                ->label('Resume')
                ->visible(fn (Subscription $record): bool => $record->getRawOriginal('status') === 'paused')
                ->action(fn (Subscription $record): Subscription => app(ResumeSubscription::class)->execute($record)),
            Action::make('cancel')
                ->label('Cancel')
                ->requiresConfirmation()
                ->visible(fn (Subscription $record): bool => ! in_array($record->getRawOriginal('status'), ['cancelled', 'expired'], true))
                ->action(fn (Subscription $record): Subscription => app(CancelSubscription::class)->execute($record)),
            Action::make('expire')
                ->label('Expire')
                ->requiresConfirmation()
                ->visible(fn (Subscription $record): bool => $record->current_period_ends_at?->isPast() === true && ! $record->auto_renew && ! in_array($record->getRawOriginal('status'), ['cancelled', 'expired'], true))
                ->action(function (Subscription $record): void {
                    app(ExpireSubscriptions::class)->execute($record->team_id, now());
                }),
            Action::make('change_plan')
                ->label('Change plan')
                ->form([TextInput::make('pricing_plan_id')->integer()->minValue(1)->required()])
                ->visible(fn (Subscription $record): bool => ! in_array($record->getRawOriginal('status'), ['cancelled', 'expired'], true))
                ->action(fn (Subscription $record, array $data): Subscription => app(ChangeSubscriptionPlan::class)->execute($record, (int) $data['pricing_plan_id'])),
        ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListSubscriptions::route('/'),
            'create' => CreateSubscription::route('/create'),
        ];
    }
}
