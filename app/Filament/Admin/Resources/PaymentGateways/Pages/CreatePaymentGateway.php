<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\PaymentGateways\Pages;

use App\Filament\Admin\Resources\PaymentGateways\PaymentGatewayResource;
use Filament\Resources\Pages\CreateRecord;
use Override;

class CreatePaymentGateway extends CreateRecord
{
    #[Override]
    protected static string $resource = PaymentGatewayResource::class;
}
