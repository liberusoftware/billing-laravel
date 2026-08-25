<?php

declare(strict_types=1);

namespace Liberu\Billing\Usage\Filament\Resources\UsageRecordResource\Pages;

use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Liberu\Billing\Usage\Actions\IngestUsage;
use Liberu\Billing\Usage\Filament\Resources\UsageRecordResource;
use Liberu\Billing\Usage\Models\Meter;

final class CreateUsageRecord extends CreateRecord
{
    protected static string $resource = UsageRecordResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        $team = (int) (data_get(auth()->user(), 'current_team_id') ?? data_get(auth()->user(), 'currentTeam.id'));
        $meter = Meter::query()->whereKey($data['meter_id'])->where(fn ($query) => $query->whereNull('team_id')->orWhere('team_id', $team))->firstOrFail();

        return app(IngestUsage::class)->execute($meter, ['event_key' => $data['event_key'], 'customer_id' => $data['customer_id'] ?? null, 'quantity' => $data['quantity']]);
    }
}
