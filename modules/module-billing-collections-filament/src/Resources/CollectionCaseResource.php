<?php

declare(strict_types=1);

namespace Liberu\Billing\Collections\Filament\Resources;

use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Gate;
use Liberu\Billing\Collections\Actions\ApplyCreditControl;
use Liberu\Billing\Collections\Actions\PromisePayment;
use Liberu\Billing\Collections\Actions\RecoverCollectionCase;
use Liberu\Billing\Collections\Actions\RetryCollectionCase;
use Liberu\Billing\Collections\Actions\ScheduleDunning;
use Liberu\Billing\Collections\Actions\ScheduleReminder;
use Liberu\Billing\Collections\Actions\SuspendCollectionCase;
use Liberu\Billing\Collections\Actions\WriteOffCollectionCase;
use Liberu\Billing\Collections\Enums\CollectionStatus;
use Liberu\Billing\Collections\Filament\Concerns\ScopesCurrentTeam;
use Liberu\Billing\Collections\Filament\Resources\CollectionCaseResource\Pages\CreateCollectionCase;
use Liberu\Billing\Collections\Filament\Resources\CollectionCaseResource\Pages\ListCollectionCases;
use Liberu\Billing\Collections\Models\CollectionCase;

final class CollectionCaseResource extends Resource
{
    protected static string|\UnitEnum|null $navigationGroup = 'Billing Operations';

    use ScopesCurrentTeam;

    protected static ?string $model = CollectionCase::class;

    // The module's scope trait handles nullable team IDs explicitly; do not
    // let Filament infer a `team` relationship that this value object lacks.
    protected static bool $isScopedToTenant = false;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-banknotes';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([TextInput::make('amount_minor')->required()->integer()->minValue(1), TextInput::make('currency')->required()->length(3), TextInput::make('customer_id')->integer()->minValue(1), TextInput::make('type')->default('dunning')]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('id')->label('Case')->sortable(),
            TextColumn::make('invoice_id')->label('Invoice')->sortable()->searchable(),
            TextColumn::make('customer_id')->label('Customer')->sortable()->searchable(),
            TextColumn::make('status')->badge()->sortable(),
            TextColumn::make('type')->badge()->sortable(),
            TextColumn::make('amount_minor')->label('Amount (minor units)')->numeric()->sortable(),
            TextColumn::make('currency')->sortable(),
            TextColumn::make('reason')->wrap()->placeholder('—'),
            TextColumn::make('next_action_at')->dateTime()->sortable(),
            TextColumn::make('created_at')->dateTime()->sortable(),
        ])->filters([
            SelectFilter::make('status')->options(collect(CollectionStatus::cases())->mapWithKeys(
                fn (CollectionStatus $status): array => [$status->value => ucfirst(str_replace('_', ' ', $status->value))]
            )->all()),
            SelectFilter::make('type')->options([
                'retry' => 'Retry', 'dunning' => 'Dunning', 'reminder' => 'Reminder', 'promise' => 'Promise',
                'credit_control' => 'Credit control', 'suspension' => 'Suspension', 'write_off' => 'Write-off', 'recovery' => 'Recovery',
            ]),
            Filter::make('needs_attention')->label('Needs attention')->default()
                ->query(fn (Builder $query) => $query->whereIn('status', [CollectionStatus::Open->value, CollectionStatus::Promised->value, CollectionStatus::Suspended->value])
                    ->where(function (Builder $query): void {
                        $query->whereNull('next_action_at')->orWhere('next_action_at', '<=', now());
                    })),
        ])->actions([
            Action::make('promise')->form([TextInput::make('due_at')->type('datetime-local')->required()])->action(function (CollectionCase $record, array $data): void {
                Gate::authorize('update', $record);
                app(PromisePayment::class)->execute($record, new \DateTimeImmutable($data['due_at']));
            }),
            Action::make('retry')->requiresConfirmation()->form([TextInput::make('next_action_at')->type('datetime-local')->required()])->action(function (CollectionCase $record, array $data): void {
                Gate::authorize('update', $record);
                app(RetryCollectionCase::class)->execute($record, new \DateTimeImmutable($data['next_action_at']));
            }),
            Action::make('dunning')->form([TextInput::make('next_action_at')->type('datetime-local')->required()])->action(function (CollectionCase $record, array $data): void {
                Gate::authorize('update', $record);
                app(ScheduleDunning::class)->execute($record, new \DateTimeImmutable($data['next_action_at']));
            }),
            Action::make('reminder')->form([TextInput::make('next_action_at')->type('datetime-local')->required()])->action(function (CollectionCase $record, array $data): void {
                Gate::authorize('update', $record);
                app(ScheduleReminder::class)->execute($record, new \DateTimeImmutable($data['next_action_at']));
            }),
            Action::make('suspend')->form([TextInput::make('reason')->required()->maxLength(1000)])->action(function (CollectionCase $record, array $data): void {
                Gate::authorize('update', $record);
                app(SuspendCollectionCase::class)->execute($record, $data['reason']);
            }),
            Action::make('write_off')->form([TextInput::make('reason')->required()->maxLength(1000)])->action(function (CollectionCase $record, array $data): void {
                Gate::authorize('update', $record);
                app(WriteOffCollectionCase::class)->execute($record, $data['reason']);
            }),
            Action::make('recover')->requiresConfirmation()->action(function (CollectionCase $record): void {
                Gate::authorize('update', $record);
                app(RecoverCollectionCase::class)->execute($record);
            }),
            Action::make('credit_control')->form([Select::make('level')->options(['notice' => 'Notice', 'warning' => 'Warning', 'final' => 'Final'])->required(), TextInput::make('reason')->maxLength(1000)])->action(function (CollectionCase $record, array $data): void {
                Gate::authorize('update', $record);
                app(ApplyCreditControl::class)->execute($record, $data['level'], $data['reason'] ?? null);
            }),
        ])->defaultSort('id', 'desc');
    }

    public static function getPages(): array
    {
        return ['index' => ListCollectionCases::route('/'), 'create' => CreateCollectionCase::route('/create')];
    }
}
