<?php

declare(strict_types=1);

namespace Liberu\Billing\Subscriptions\Filament\Resources\SubscriptionResource\Pages;

use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Liberu\Billing\Subscriptions\Filament\Resources\SubscriptionResource;

final class ListSubscriptions extends ListRecords
{
    protected static string $resource = SubscriptionResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }
}
