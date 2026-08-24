<?php

declare(strict_types=1);

namespace Liberu\Billing\CustomerPortal\Filament\Resources;

use Filament\Resources\Resource;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Liberu\Billing\CustomerPortal\Models\PortalItem;

final class PortalItemResource extends Resource
{
    protected static ?string $model = PortalItem::class;

    public static function table(Table $table): Table
    {
        return $table->columns([TextColumn::make('type')->badge(), TextColumn::make('subject')->searchable(), TextColumn::make('status')->badge()]);
    }
}
