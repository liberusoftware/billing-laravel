<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\PaymentGateways\Pages;

use App\Filament\Admin\Resources\PaymentGateways\PaymentGatewayResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Override;

class ListPaymentGateways extends ListRecords
{
    #[Override]
    protected static string $resource = PaymentGatewayResource::class;

    #[Override]
    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
