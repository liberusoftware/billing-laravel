<?php

declare(strict_types=1);

namespace App\Filament\App\Resources\PartialPayments\Pages;

use App\Filament\App\Resources\PartialPayments\PartialPaymentResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Override;

class EditPartialPayment extends EditRecord
{
    #[Override]
    protected static string $resource = PartialPaymentResource::class;

    #[Override]
    protected function getActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
