<?php

declare(strict_types=1);

namespace Liberu\Billing\Payments\Filament\Resources\PaymentMethodResource\Pages;

use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Liberu\Billing\Payments\Actions\CreatePaymentMethod as CreatePaymentMethodAction;
use Liberu\Billing\Payments\Filament\Resources\PaymentMethodResource;

final class CreatePaymentMethod extends CreateRecord
{
    protected static string $resource = PaymentMethodResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        $data['team_id'] = data_get(auth()->user(), 'current_team_id') ?? data_get(auth()->user(), 'currentTeam.id');

        return app(CreatePaymentMethodAction::class)->execute($data);
    }
}
