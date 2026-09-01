<?php

declare(strict_types=1);

namespace Liberu\Billing\Core\Actions;

use Illuminate\Database\DatabaseManager;
use Illuminate\Database\Eloquent\Model;
use Liberu\Billing\Core\Events\BillingRecordDeleted;

final readonly class DeleteBillingRecord
{
    public function __construct(private DatabaseManager $database) {}

    public function execute(Model $record, string $recordType): void
    {
        $recordId = $record->getKey();
        $teamId = (int) ($record->getAttribute('team_id') ?? 0);

        $this->database->transaction(function () use ($record, $recordId, $teamId, $recordType): void {
            $locked = $record->newQuery()->lockForUpdate()->findOrFail($record->getKey());
            $locked->delete();
            BillingRecordDeleted::dispatch($recordType, $recordId, $teamId);
        });
    }
}
