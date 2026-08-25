<?php

declare(strict_types=1);

namespace Liberu\Billing\Payments\Filament\Resources\PaymentMethodResource\Pages;

use Filament\Resources\Pages\ListRecords;
use Liberu\Billing\Payments\Filament\Resources\PaymentMethodResource;

final class ListPaymentMethods extends ListRecords
{
    protected static string $resource = PaymentMethodResource::class;
}
