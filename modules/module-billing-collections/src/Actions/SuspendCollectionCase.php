<?php

declare(strict_types=1);

namespace Liberu\Billing\Collections\Actions;

use Illuminate\Database\DatabaseManager;
use Liberu\Billing\Collections\Enums\CollectionStatus;
use Liberu\Billing\Collections\Models\CollectionCase;

final readonly class SuspendCollectionCase
{
    public function __construct(private DatabaseManager $database) {}

    public function execute(CollectionCase $case, string $reason): CollectionCase
    {
        $reason = trim($reason);
        if ($reason === '' || $case->status === CollectionStatus::Closed || $case->status === CollectionStatus::WrittenOff) {
            throw new \LogicException('This collection case cannot be suspended.');
        }

        return $this->database->transaction(function () use ($case, $reason): CollectionCase {
            $locked = CollectionCase::query()->lockForUpdate()->findOrFail($case->getKey());
            if ($locked->status === CollectionStatus::Closed || $locked->status === CollectionStatus::WrittenOff) {
                throw new \LogicException('This collection case cannot be suspended.');
            }

            $locked->update(['status' => CollectionStatus::Suspended, 'reason' => $reason]);

            return $locked->refresh();
        });
    }
}
