<?php

declare(strict_types=1);

namespace Liberu\Billing\Orders\Filament\Resources\OrderResource\Pages;

use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Liberu\Billing\Orders\Actions\CreateOrder as CreateOrderAction;
use Liberu\Billing\Orders\Filament\Resources\OrderResource;

final class CreateOrder extends CreateRecord
{
    protected static string $resource = OrderResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        $teamId = data_get(auth()->user(), 'current_team_id') ?? data_get(auth()->user(), 'currentTeam.id');
        $data['team_id'] = $teamId === null ? null : (int) $teamId;

        return app(CreateOrderAction::class)->execute($data);
    }
}
