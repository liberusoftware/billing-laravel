<?php

declare(strict_types=1);

namespace Liberu\Billing\Domains\Filament\Resources;

use Filament\Resources\Resource;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Liberu\Billing\Domains\Filament\Concerns\ScopesCurrentTeam;
use Liberu\Billing\Domains\Models\DnsRecord;

final class DnsRecordResource extends Resource
{
    protected static string|\UnitEnum|null $navigationGroup = 'Service Delivery';

    use ScopesCurrentTeam;

    protected static ?string $model = DnsRecord::class;

    public static function table(Table $table): Table
    {
        return $table->columns([TextColumn::make('type')->badge(), TextColumn::make('host')->searchable(), TextColumn::make('value')->searchable(), TextColumn::make('ttl')]);
    }
}
