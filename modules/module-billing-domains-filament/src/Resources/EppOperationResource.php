<?php

declare(strict_types=1);

namespace Liberu\Billing\Domains\Filament\Resources;

use Filament\Resources\Resource;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Liberu\Billing\Domains\Models\EppOperation;

final class EppOperationResource extends Resource
{
    protected static string|\UnitEnum|null $navigationGroup = 'Service Delivery';

    protected static ?string $model = EppOperation::class;

    public static function table(Table $table): Table
    {
        return $table->columns([TextColumn::make('domain_id'), TextColumn::make('operation')->badge(), TextColumn::make('status')->badge(), TextColumn::make('created_at')->dateTime()])->defaultSort('id', 'desc');
    }

    public static function getEloquentQuery(): Builder
    {
        $team = data_get(auth()->user(), 'current_team_id') ?? data_get(auth()->user(), 'currentTeam.id');

        return parent::getEloquentQuery()->where('team_id', $team);
    }
}
