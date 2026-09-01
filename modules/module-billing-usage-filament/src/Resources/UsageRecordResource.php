<?php

declare(strict_types=1);

namespace Liberu\Billing\Usage\Filament\Resources;

use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Liberu\Billing\Usage\Filament\Concerns\ScopesCurrentTeam;
use Liberu\Billing\Usage\Filament\Resources\UsageRecordResource\Pages\CreateUsageRecord;
use Liberu\Billing\Usage\Filament\Resources\UsageRecordResource\Pages\ListUsageRecords;
use Liberu\Billing\Usage\Models\UsageRecord;

final class UsageRecordResource extends Resource
{
    protected static string|\UnitEnum|null $navigationGroup = 'Billing Operations';

    use ScopesCurrentTeam;

    protected static ?string $model = UsageRecord::class;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([TextInput::make('meter_id')->required()->integer()->minValue(1), TextInput::make('event_key')->required()->maxLength(255), TextInput::make('customer_id')->integer()->minValue(1), TextInput::make('quantity')->required()->numeric()->minValue(0.00001)]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([TextColumn::make('event_key')->searchable(), TextColumn::make('meter_id'), TextColumn::make('customer_id'), TextColumn::make('quantity'), TextColumn::make('amount_minor'), TextColumn::make('occurred_at')->dateTime()]);
    }

    public static function getPages(): array
    {
        return ['index' => ListUsageRecords::route('/'), 'create' => CreateUsageRecord::route('/create')];
    }
}
