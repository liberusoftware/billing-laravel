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
        if (in_array($case->status, [CollectionStatus::Recovered, CollectionStatus::WrittenOff, CollectionStatus::Closed], true)) {
            throw new \LogicException('This collection case cannot be written off.');
        }

        return $this->database->transaction(function () use ($case, $reason): CollectionCase {
            $case->update(['status' => CollectionStatus::WrittenOff, 'reason' => $reason]);

            return $case->refresh();
        });
    }
}
