<?php

declare(strict_types=1);

namespace Liberu\Billing\Communications\Filament\Resources;

use Filament\Resources\Resource;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Liberu\Billing\Communications\Models\CommunicationUsageImport;

final class CommunicationUsageImportResource extends Resource
{
    protected static ?string $model = CommunicationUsageImport::class;

    public static function table(Table $table): Table
    {
        return $table->columns([TextColumn::make('provider'), TextColumn::make('rows'), TextColumn::make('total_amount_minor'), TextColumn::make('status')->badge()]);
    }
}
