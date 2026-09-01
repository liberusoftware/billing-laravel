<?php

declare(strict_types=1);

namespace Liberu\Billing\Core\Filament\Resources\Pages;

use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;
use Liberu\Billing\Core\Actions\UpdateBillingRecord;

abstract class EditBillingCoreRecord extends EditRecord
{
    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        unset($data['team_id']);

        return app(UpdateBillingRecord::class)->execute($record, $data);
    }
}
