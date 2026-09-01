<?php

declare(strict_types=1);

namespace Liberu\Billing\Provisioning\Filament\Resources;

use Filament\Actions\Action;
use Filament\Resources\Resource;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Gate;
use Liberu\Billing\Provisioning\Actions\RunProvisioningOperation;
use Liberu\Billing\Provisioning\Filament\Concerns\ScopesCurrentTeam;
use Liberu\Billing\Provisioning\Models\ProvisioningOperation;

final class ProvisioningOperationResource extends Resource
{
    protected static string|\UnitEnum|null $navigationGroup = 'Service Delivery';

    use ScopesCurrentTeam;

    protected static ?string $model = ProvisioningOperation::class;

    public static function table(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('operation')->badge(),
            TextColumn::make('provisioned_service_id'),
            TextColumn::make('status')->badge(),
            TextColumn::make('attempts'),
            TextColumn::make('error')->limit(60),
        ])->actions([
            Action::make('run')
                ->label('Run operation')
                ->requiresConfirmation()
                ->visible(fn (ProvisioningOperation $record): bool => in_array($record->getRawOriginal('status'), ['queued', 'failed'], true))
                ->action(function (ProvisioningOperation $record, RunProvisioningOperation $run): void {
                    Gate::authorize('update', $record);
                    $run->execute($record);
                }),
        ]);
    }
}
