<?php

declare(strict_types=1);

namespace Liberu\Billing\Pricing\Filament\Resources;

use Filament\Resources\Resource;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Liberu\Billing\Pricing\Filament\Concerns\ScopesCurrentTeam;
use Liberu\Billing\Pricing\Models\PricingContract;

final class PricingContractResource extends Resource
{
    use ScopesCurrentTeam;

    protected static ?string $model = PricingContract::class;

    public static function table(Table $table): Table
    {
        return $table->columns([TextColumn::make('pricing_plan_id'), TextColumn::make('customer_id'), TextColumn::make('status')->badge(), TextColumn::make('starts_at')->dateTime()]);
    }
}
