<?php

declare(strict_types=1);

namespace Liberu\Billing\Invoicing\Filament\Resources\InvoiceSupportResource\Pages;

use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Liberu\Billing\Invoicing\Actions\CreateInvoiceSupport as CreateInvoiceSupportAction;
use Liberu\Billing\Invoicing\Filament\Resources\InvoiceSupportResource;

final class CreateInvoiceSupport extends CreateRecord
{
    protected static string $resource = InvoiceSupportResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        $team = data_get(auth()->user(), 'current_team_id') ?? data_get(auth()->user(), 'currentTeam.id');

        return app(CreateInvoiceSupportAction::class)->execute((int) $team, $data);
    }
}
