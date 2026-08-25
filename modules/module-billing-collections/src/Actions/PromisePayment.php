<?php

declare(strict_types=1);

namespace Liberu\Billing\Collections\Actions;

use Illuminate\Database\DatabaseManager;
use Liberu\Billing\Collections\Enums\CollectionStatus;
use Liberu\Billing\Collections\Models\CollectionCase;

final readonly class PromisePayment
{
    public function __construct(private DatabaseManager $database) {}

    public function execute(CollectionCase $case, \DateTimeInterface $dueAt): CollectionCase
    {
        if (in_array($case->status, [CollectionStatus::WrittenOff, CollectionStatus::Recovered, CollectionStatus::Closed], true)) {
            throw new \LogicException('This collection case cannot accept a promise.');
        }

        return $this->database->transaction(function () use ($case, $dueAt): CollectionCase {
            $case->update(['status' => CollectionStatus::Promised, 'promise_due_at' => $dueAt]);

            return $case->refresh();
        });
    }
}
