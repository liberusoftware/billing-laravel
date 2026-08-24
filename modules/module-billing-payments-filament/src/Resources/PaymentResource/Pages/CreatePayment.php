<?php

declare(strict_types=1);

namespace Liberu\Billing\Payments\Filament\Resources\PaymentResource\Pages;

use Filament\Resources\Pages\CreateRecord;
use Liberu\Billing\Payments\Actions\CreatePayment as CreatePaymentAction;
use Liberu\Billing\Payments\Filament\Resources\PaymentResource;
use Liberu\Billing\Payments\Models\Payment;

final class CreatePayment extends CreateRecord
{
    protected static string $resource = PaymentResource::class;

    protected function handleRecordCreation(array $data): Payment
    {
        $teamId = data_get(auth()->user(), 'current_team_id') ?? data_get(auth()->user(), 'currentTeam.id');
        $data['team_id'] = $teamId === null ? null : (int) $teamId;

        return app(CreatePaymentAction::class)->execute($data);
    }
}
