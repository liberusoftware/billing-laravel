<?php

declare(strict_types=1);

namespace Liberu\Billing\Payments\Filament\Resources\PaymentDisputeResource\Pages;

use Filament\Resources\Pages\ListRecords;
use Liberu\Billing\Payments\Filament\Resources\PaymentDisputeResource;

final class ListPaymentDisputes extends ListRecords
{
    protected static string $resource = PaymentDisputeResource::class;
}
