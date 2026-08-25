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
use Filament\Tables\Table;
use Illuminate\Support\Facades\Gate;
use Liberu\Billing\Collections\Actions\ApplyCreditControl;
use Liberu\Billing\Collections\Actions\PromisePayment;
use Liberu\Billing\Collections\Actions\RecoverCollectionCase;
use Liberu\Billing\Collections\Actions\RetryCollectionCase;
use Liberu\Billing\Collections\Actions\ScheduleDunning;
use Liberu\Billing\Collections\Actions\ScheduleReminder;
use Liberu\Billing\Collections\Actions\SuspendCollectionCase;
use Liberu\Billing\Collections\Actions\WriteOffCollectionCase;
use Liberu\Billing\Collections\Filament\Concerns\ScopesCurrentTeam;
use Liberu\Billing\Collections\Filament\Resources\CollectionCaseResource\Pages\CreateCollectionCase;
use Liberu\Billing\Collections\Filament\Resources\CollectionCaseResource\Pages\ListCollectionCases;
use Liberu\Billing\Collections\Models\CollectionCase;

final class CollectionCaseResource extends Resource
{
    use ScopesCurrentTeam;

    protected static ?string $model = CollectionCase::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-banknotes';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([TextInput::make('amount_minor')->required()->integer()->minValue(1), TextInput::make('currency')->required()->length(3), TextInput::make('customer_id')->integer()->minValue(1), TextInput::make('type')->default('dunning')]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([TextColumn::make('id')->sortable(), TextColumn::make('status')->badge(), TextColumn::make('type'), TextColumn::make('amount_minor'), TextColumn::make('currency')])->actions([
            Action::make('promise')->form([TextInput::make('due_at')->type('datetime-local')->required()])->action(function (CollectionCase $record, array $data): void {
                Gate::authorize('update', $record);
                app(PromisePayment::class)->execute($record, new \DateTimeImmutable($data['due_at']));
            }),
            Action::make('retry')->form([TextInput::make('next_action_at')->type('datetime-local')->required()])->action(function (CollectionCase $record, array $data): void {
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
