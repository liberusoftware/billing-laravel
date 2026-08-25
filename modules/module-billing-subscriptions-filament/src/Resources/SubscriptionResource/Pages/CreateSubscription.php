<?php

declare(strict_types=1);

namespace Liberu\Billing\Subscriptions\Filament\Resources\SubscriptionResource\Pages;

use Filament\Resources\Pages\CreateRecord;
use Liberu\Billing\Subscriptions\Actions\ActivateSubscription;
use Liberu\Billing\Subscriptions\Filament\Resources\SubscriptionResource;
use Liberu\Billing\Subscriptions\Models\Subscription;

final class CreateSubscription extends CreateRecord
{
    protected static string $resource = SubscriptionResource::class;

    protected function handleRecordCreation(array $data): Subscription
    {
        $teamId = data_get(auth()->user(), 'current_team_id') ?? data_get(auth()->user(), 'currentTeam.id');
        $data['team_id'] = $teamId === null ? null : (int) $teamId;

        return app(ActivateSubscription::class)->execute($data);
    }
}
