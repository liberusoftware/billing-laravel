<?php

declare(strict_types=1);

namespace Liberu\Billing\Domains\Filament\Resources\DomainTldResource\Pages;

use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Liberu\Billing\Domains\Actions\UpsertDomainTld;
use Liberu\Billing\Domains\Filament\Resources\DomainTldResource;

final class CreateDomainTld extends CreateRecord
{
    protected static string $resource = DomainTldResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        return app(UpsertDomainTld::class)->execute($data);
    }
}
