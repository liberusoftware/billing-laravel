<?php

declare(strict_types=1);

namespace Liberu\Billing\Payments\Filament\Resources\PaymentMandateResource\Pages;

use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Liberu\Billing\Payments\Actions\CreatePaymentMandate as CreatePaymentMandateAction;
use Liberu\Billing\Payments\Filament\Resources\PaymentMandateResource;

final class CreatePaymentMandate extends CreateRecord
{
    protected static string $resource = PaymentMandateResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        $data['team_id'] = data_get(auth()->user(), 'current_team_id') ?? data_get(auth()->user(), 'currentTeam.id');

        return app(CreatePaymentMandateAction::class)->execute($data);
    }
}
