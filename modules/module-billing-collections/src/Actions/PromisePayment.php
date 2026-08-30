<?php

declare(strict_types=1);

namespace Liberu\Billing\Collections\Actions;

use Illuminate\Database\DatabaseManager;
use Liberu\Billing\Collections\Enums\CollectionStatus;
use Liberu\Billing\Collections\Events\PaymentPromised;
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
            $locked = CollectionCase::query()->lockForUpdate()->findOrFail($case->getKey());
            if (in_array($locked->status, [CollectionStatus::WrittenOff, CollectionStatus::Recovered, CollectionStatus::Closed], true)) {
                throw new \LogicException('This collection case cannot accept a promise.');
            }

            $locked->update(['status' => CollectionStatus::Promised, 'promise_due_at' => $dueAt]);
            $updated = $locked->refresh();
            PaymentPromised::dispatch($updated);

            return $updated;
        });
    }
}
