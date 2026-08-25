<?php

declare(strict_types=1);

namespace Liberu\Billing\Collections\Actions;

use Illuminate\Database\DatabaseManager;
use Liberu\Billing\Collections\Enums\CollectionStatus;
use Liberu\Billing\Collections\Models\CollectionCase;

final readonly class ApplyCreditControl
{
    public function __construct(private DatabaseManager $database) {}

    public function execute(CollectionCase $case, string $level, ?string $reason = null): CollectionCase
    {
        $level = trim($level);
        if ($level === '') {
            throw new \InvalidArgumentException('A credit-control level is required.');
        }
        if (in_array($case->status, [CollectionStatus::Recovered, CollectionStatus::WrittenOff, CollectionStatus::Closed], true)) {
            throw new \LogicException('Credit control cannot be applied to a terminal case.');
        }

        return $this->database->transaction(function () use ($case, $level, $reason): CollectionCase {
            $metadata = $case->metadata ?? [];
            $metadata['credit_control_level'] = $level;
            $case->update(['type' => 'credit_control', 'reason' => $reason ?: $case->reason, 'metadata' => $metadata]);

            return $case->refresh();
        });
    }
}
