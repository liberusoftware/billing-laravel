<?php

declare(strict_types=1);

namespace Liberu\Billing\Collections\Actions;

use Illuminate\Database\DatabaseManager;
use Liberu\Billing\Collections\Enums\CollectionStatus;
use Liberu\Billing\Collections\Models\CollectionCase;

final readonly class ScheduleReminder
{
    public function __construct(private DatabaseManager $database) {}

    public function execute(CollectionCase $case, \DateTimeInterface $nextActionAt): CollectionCase
    {
        if (in_array($case->status, [CollectionStatus::Recovered, CollectionStatus::WrittenOff, CollectionStatus::Closed], true)) {
            throw new \LogicException('This collection case cannot receive a reminder.');
        }

        return $this->database->transaction(function () use ($case, $nextActionAt): CollectionCase {
            $locked = CollectionCase::query()->lockForUpdate()->findOrFail($case->getKey());
            if (in_array($locked->status, [CollectionStatus::Recovered, CollectionStatus::WrittenOff, CollectionStatus::Closed], true)) {
                throw new \LogicException('This collection case cannot receive a reminder.');
            }

            $metadata = $locked->metadata ?? [];
            $metadata['reminder_scheduled_at'] = now()->toIso8601String();
            $locked->update(['type' => 'reminder', 'next_action_at' => $nextActionAt, 'metadata' => $metadata]);

            return $locked->refresh();
        });
    }
}
