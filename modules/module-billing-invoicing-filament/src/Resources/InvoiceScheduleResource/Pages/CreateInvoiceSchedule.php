<?php

declare(strict_types=1);

namespace Liberu\Billing\Invoicing\Filament\Resources\InvoiceScheduleResource\Pages;

use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Liberu\Billing\Invoicing\Actions\CreateInvoiceSchedule as CreateInvoiceScheduleAction;
use Liberu\Billing\Invoicing\Filament\Resources\InvoiceScheduleResource;

final class CreateInvoiceSchedule extends CreateRecord
{
    protected static string $resource = InvoiceScheduleResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        $teamId = data_get(auth()->user(), 'current_team_id') ?? data_get(auth()->user(), 'currentTeam.id');
        $data['team_id'] = $teamId;

        return app(CreateInvoiceScheduleAction::class)->execute($data);
    }
}
