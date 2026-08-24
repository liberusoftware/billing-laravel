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
        if ($case->status === CollectionStatus::Closed || $case->status === CollectionStatus::WrittenOff) {
            throw new \LogicException('This collection case cannot be suspended.');
        }

        return $this->database->transaction(function () use ($case, $reason): CollectionCase {
            $case->update(['status' => CollectionStatus::Suspended, 'reason' => $reason]);

            return $case->refresh();
        });
    }
}
