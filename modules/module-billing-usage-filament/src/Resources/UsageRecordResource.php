<?php

declare(strict_types=1);

namespace Liberu\Billing\Usage\Filament\Resources;

use Filament\Resources\Resource;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Liberu\Billing\Usage\Models\UsageRecord;

final class UsageRecordResource extends Resource
{
    protected static ?string $model = UsageRecord::class;
    public static function table(Table $table): Table { return $table->columns([TextColumn::make('event_key')->searchable(), TextColumn::make('meter_id'), TextColumn::make('customer_id'), TextColumn::make('quantity'), TextColumn::make('amount_minor'), TextColumn::make('occurred_at')->dateTime()]); }
}
