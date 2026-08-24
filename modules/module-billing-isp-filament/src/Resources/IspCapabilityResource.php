<?php

declare(strict_types=1);

namespace Liberu\Billing\Isp\Filament\Resources;

use Filament\Resources\Resource;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Liberu\Billing\Isp\Models\IspCapability;

final class IspCapabilityResource extends Resource
{
    protected static ?string $model = IspCapability::class;

    public static function table(Table $table): Table
    {
        return $table->columns([TextColumn::make('type')->badge(), TextColumn::make('name')->searchable(), TextColumn::make('identifier'), TextColumn::make('status')->badge()]);
    }
}
