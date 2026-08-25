<?php

declare(strict_types=1);

namespace Liberu\Billing\Payments\Filament\Resources\PaymentRefundResource\Pages;

use Filament\Resources\Pages\ListRecords;
use Liberu\Billing\Payments\Filament\Resources\PaymentRefundResource;

final class ListPaymentRefunds extends ListRecords
{
    protected static string $resource = PaymentRefundResource::class;
}
