<?php

declare(strict_types=1);

namespace Liberu\Billing\Provisioning\Filament\Resources;

use Filament\Actions\Action;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Gate;
use Liberu\Billing\Provisioning\Actions\ReconcileProvisionedService;
use Liberu\Billing\Provisioning\Filament\Resources\ProvisionedServiceResource\Pages\CreateProvisionedService;
use Liberu\Billing\Provisioning\Filament\Resources\ProvisionedServiceResource\Pages\ListProvisionedServices;
use Liberu\Billing\Provisioning\Models\ProvisionedService;

final class ProvisionedServiceResource extends Resource
{
    protected static ?string $model = ProvisionedService::class;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([TextInput::make('provider')->required(), TextInput::make('state')->required()->default('pending'), TextInput::make('external_id')]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('provider')->badge(),
            TextColumn::make('state')->badge(),
            TextColumn::make('external_id'),
            TextColumn::make('last_reconciled_at')->dateTime(),
        ])->actions([
            Action::make('reconcile')
                ->label('Reconcile')
                ->requiresConfirmation()
                ->action(function (ProvisionedService $record, ReconcileProvisionedService $reconcile): void {
                    Gate::authorize('update', $record);
                    $reconcile->execute($record);
                }),
        ]);
    }

    public static function getPages(): array
    {
        return ['index' => ListProvisionedServices::route('/'), 'create' => CreateProvisionedService::route('/create')];
    }
}
