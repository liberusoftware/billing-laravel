<?php

declare(strict_types=1);

namespace Liberu\Billing\Pricing\Filament\Resources;

use Filament\Resources\Resource;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Liberu\Billing\Pricing\Filament\Concerns\ScopesCurrentTeam;
use Liberu\Billing\Pricing\Models\PricingSnapshot;

final class PricingSnapshotResource extends Resource
{
    use ScopesCurrentTeam;

    protected static ?string $model = PricingSnapshot::class;

    public static function table(Table $table): Table
    {
        return $table->columns([TextColumn::make('pricing_plan_id'), TextColumn::make('version'), TextColumn::make('captured_at')->dateTime()]);
    }
}
