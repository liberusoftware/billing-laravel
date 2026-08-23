<?php

namespace App\Filament\App\Resources\Refunds\Pages;

use App\Filament\App\Resources\Refunds\RefundResource;
use Filament\Resources\Pages\CreateRecord;
use Override;

class CreateRefund extends CreateRecord
{
    #[Override]
    protected static string $resource = RefundResource::class;

    #[Override]
    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
