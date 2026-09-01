<?php

declare(strict_types=1);

namespace Liberu\Billing\Isp\Filament\Resources;

use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Gate;
use Liberu\Billing\Isp\Actions\RecordRadiusAccounting;
use Liberu\Billing\Isp\Actions\ResetUsagePeriod;
use Liberu\Billing\Isp\Actions\SynchronizeAccessService;
use Liberu\Billing\Isp\Actions\TransitionAccessService;
use Liberu\Billing\Isp\Filament\Concerns\ScopesCurrentTeam;
use Liberu\Billing\Isp\Filament\Resources\AccessServiceResource\Pages\CreateAccessService;
use Liberu\Billing\Isp\Filament\Resources\AccessServiceResource\Pages\ListAccessServices;
use Liberu\Billing\Isp\Models\AccessService;

final class AccessServiceResource extends Resource
{
    protected static string|\UnitEnum|null $navigationGroup = 'Service Delivery';

    use ScopesCurrentTeam;

    protected static ?string $model = AccessService::class;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([TextInput::make('name')->required()->maxLength(255), TextInput::make('status')->required()->default('active')->maxLength(32), TextInput::make('monthly_data_limit_bytes')->numeric()->minValue(0)]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([TextColumn::make('name')->searchable(), TextColumn::make('status')->badge(), TextColumn::make('current_period_usage_bytes')->label('Usage bytes'), TextColumn::make('monthly_data_limit_bytes')->label('Limit bytes'), TextColumn::make('created_at')->dateTime()])->actions([
            Action::make('transition')->label('Update status')->form([Select::make('status')->options(['pending' => 'Pending', 'active' => 'Active', 'suspended' => 'Suspended', 'cancelled' => 'Cancelled', 'failed' => 'Failed'])->required()])->action(function (AccessService $record, array $data): void {
                Gate::authorize('update', $record);
                app(TransitionAccessService::class)->handle($record, $data['status']);
            }),
            Action::make('synchronize')->form([TextInput::make('adapter')->required()->maxLength(100)])->action(function (AccessService $record, array $data): void {
                Gate::authorize('update', $record);
                app(SynchronizeAccessService::class)->execute($record, $data['adapter']);
            }),
            Action::make('resetUsage')->label('Reset usage')->requiresConfirmation()->action(function (AccessService $record): void {
                Gate::authorize('update', $record);
                app(ResetUsagePeriod::class)->execute($record);
            }),
            Action::make('recordAccounting')->form([
                TextInput::make('accounting_session_id')->required(), TextInput::make('started_at')->type('datetime-local')->required(), TextInput::make('input_bytes')->numeric()->minValue(0)->default(0), TextInput::make('output_bytes')->numeric()->minValue(0)->default(0),
            ])->action(function (AccessService $record, array $data): void {
                Gate::authorize('update', $record);
                app(RecordRadiusAccounting::class)->execute($record, $data);
            }),
        ])->defaultSort('id', 'desc');
    }

    public static function getPages(): array
    {
        return ['index' => ListAccessServices::route('/'), 'create' => CreateAccessService::route('/create')];
    }
}
