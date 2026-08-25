<?php

declare(strict_types=1);

namespace Liberu\Billing\Pricing\Filament\Resources\PricingPlanResource\Pages;

use Filament\Resources\Pages\CreateRecord;
use Liberu\Billing\Pricing\Actions\CreatePricingPlan as CreatePricingPlanAction;
use Liberu\Billing\Pricing\Filament\Resources\PricingPlanResource;
use Liberu\Billing\Pricing\Models\PricingPlan;

final class CreatePricingPlan extends CreateRecord
{
    protected static string $resource = PricingPlanResource::class;

    protected function handleRecordCreation(array $data): PricingPlan
    {
        $teamId = data_get(auth()->user(), 'current_team_id') ?? data_get(auth()->user(), 'currentTeam.id');
        $data['team_id'] = $teamId === null ? null : (int) $teamId;

        return app(CreatePricingPlanAction::class)->execute($data);
    }
}
