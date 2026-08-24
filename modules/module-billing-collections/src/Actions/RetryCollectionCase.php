<?php

declare(strict_types=1);

namespace Liberu\Billing\Collections\Actions;

use Illuminate\Database\DatabaseManager;
use Liberu\Billing\Collections\Enums\CollectionStatus;
use Liberu\Billing\Collections\Models\CollectionCase;

final readonly class RetryCollectionCase
{
    public function __construct(private DatabaseManager $database) {}

    public function execute(CollectionCase $case, \DateTimeInterface $nextActionAt): CollectionCase
    {
        if (in_array($case->status, [CollectionStatus::Recovered, CollectionStatus::WrittenOff, CollectionStatus::Closed], true)) {
            throw new \LogicException('This collection case cannot be retried.');
        }

        return $this->database->transaction(function () use ($case, $nextActionAt): CollectionCase {
            $metadata = $case->metadata ?? [];
            $metadata['retry_count'] = ((int) ($metadata['retry_count'] ?? 0)) + 1;
            $case->update(['status' => CollectionStatus::Open, 'next_action_at' => $nextActionAt, 'metadata' => $metadata]);

            return $case->refresh();
        });
    }
}
