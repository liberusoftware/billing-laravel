<?php

declare(strict_types=1);

namespace Liberu\Billing\Payments\Filament\Resources\PaymentResource\Pages;

use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Liberu\Billing\Payments\Filament\Resources\PaymentResource;

final class ListPayments extends ListRecords
{
    protected static string $resource = PaymentResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }
}
