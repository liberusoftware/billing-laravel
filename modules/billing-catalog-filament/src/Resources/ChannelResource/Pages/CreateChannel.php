<?php

declare(strict_types=1);

namespace Liberu\Billing\Catalog\Filament\Resources\ChannelResource\Pages;

use Filament\Resources\Pages\CreateRecord;
use Liberu\Billing\Catalog\Actions\CreateCatalogRecord;
use Liberu\Billing\Catalog\Filament\Resources\ChannelResource;
use Liberu\Billing\Catalog\Models\Channel;

final class CreateChannel extends CreateRecord
{
    protected static string $resource = ChannelResource::class;

    protected function handleRecordCreation(array $data): Channel
    {
        $data['team_id'] = data_get(auth()->user(), 'current_team_id') ?? data_get(auth()->user(), 'currentTeam.id');

        return app(CreateCatalogRecord::class)->execute(Channel::class, $data);
    }
}
