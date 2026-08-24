<?php

declare(strict_types=1);

namespace Liberu\Billing\Provisioning\Filament\Resources;

use Filament\Resources\Resource;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Liberu\Billing\Provisioning\Models\ProvisioningOperation;

final class ProvisioningOperationResource extends Resource
{
    protected static ?string $model = ProvisioningOperation::class;

    public static function table(Table $table): Table
    {
        return $table->columns([TextColumn::make('operation')->badge(), TextColumn::make('provisioned_service_id'), TextColumn::make('status')->badge(), TextColumn::make('attempts')]);
    }
}
