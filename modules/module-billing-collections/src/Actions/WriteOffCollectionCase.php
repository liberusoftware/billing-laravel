<?php

declare(strict_types=1);

namespace Liberu\Billing\Collections\Actions;

use Illuminate\Database\DatabaseManager;
use Liberu\Billing\Collections\Enums\CollectionStatus;
use Liberu\Billing\Collections\Models\CollectionCase;

final readonly class WriteOffCollectionCase
{
    public function __construct(private DatabaseManager $database) {}

    public function execute(CollectionCase $case, string $reason): CollectionCase
    {
        $reason = trim($reason);
        if ($reason === '' || in_array($case->status, [CollectionStatus::Recovered, CollectionStatus::WrittenOff, CollectionStatus::Closed], true)) {
            throw new \LogicException('This collection case cannot be written off.');
        }

        return $this->database->transaction(function () use ($case, $reason): CollectionCase {
            $locked = CollectionCase::query()->lockForUpdate()->findOrFail($case->getKey());
            if (in_array($locked->status, [CollectionStatus::Recovered, CollectionStatus::WrittenOff, CollectionStatus::Closed], true)) {
                throw new \LogicException('This collection case cannot be written off.');
            }

            $locked->update(['status' => CollectionStatus::WrittenOff, 'reason' => $reason]);

            return $locked->refresh();
        });
    }
}
