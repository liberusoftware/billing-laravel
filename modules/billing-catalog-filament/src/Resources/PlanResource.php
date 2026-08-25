<?php

declare(strict_types=1);

namespace Liberu\Billing\Catalog\Filament\Resources;

use Liberu\Billing\Catalog\Filament\Resources\PlanResource\Pages\CreatePlan;
use Liberu\Billing\Catalog\Filament\Resources\PlanResource\Pages\ListPlans;
use Liberu\Billing\Catalog\Models\Plan;

final class PlanResource extends CatalogRecordResource
{
    protected static ?string $model = Plan::class;

    protected static ?string $navigationLabel = 'Plans';

    public static function getPages(): array
    {
        return ['index' => ListPlans::route('/'), 'create' => CreatePlan::route('/create')];
    }
}
