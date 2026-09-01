<?php

declare(strict_types=1);

namespace Liberu\Billing\Pricing\Filament\Resources\PricingContractResource\Pages;

use Filament\Resources\Pages\CreateRecord;
use Liberu\Billing\Pricing\Actions\CreatePricingContract as CreatePricingContractAction;
use Liberu\Billing\Pricing\Filament\Resources\PricingContractResource;
use Liberu\Billing\Pricing\Models\PricingContract;

final class CreatePricingContract extends CreateRecord
{
    protected static string $resource = PricingContractResource::class;

    protected function handleRecordCreation(array $data): PricingContract
    {
        $teamId = data_get(auth()->user(), 'current_team_id') ?? data_get(auth()->user(), 'currentTeam.id');
        $data['team_id'] = $teamId === null ? null : (int) $teamId;

        return app(CreatePricingContractAction::class)->execute($data);
    }
}
