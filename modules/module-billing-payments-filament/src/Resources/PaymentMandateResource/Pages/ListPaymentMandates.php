<?php

declare(strict_types=1);

namespace Liberu\Billing\Payments\Filament\Resources\PaymentMandateResource\Pages;

use Filament\Resources\Pages\ListRecords;
use Liberu\Billing\Payments\Filament\Resources\PaymentMandateResource;

final class ListPaymentMandates extends ListRecords
{
    protected static string $resource = PaymentMandateResource::class;
}
