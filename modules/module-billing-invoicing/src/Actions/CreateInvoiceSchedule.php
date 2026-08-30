<?php

declare(strict_types=1);

namespace Liberu\Billing\Invoicing\Actions;

use Illuminate\Database\DatabaseManager;
use Liberu\Billing\Invoicing\Events\InvoiceScheduleCreated;
use Liberu\Billing\Invoicing\Models\InvoiceSchedule;
use Liberu\Billing\Invoicing\Support\CustomerReference;

final readonly class CreateInvoiceSchedule
{
    public function __construct(private DatabaseManager $database) {}

    public function execute(array $attributes): InvoiceSchedule
    {
        $frequency = strtolower(trim((string) ($attributes['frequency'] ?? '')));
        if (! in_array($frequency, ['daily', 'weekly', 'monthly', 'yearly'], true)) {
            throw new \InvalidArgumentException('Invoice schedule frequency is invalid.');
        }

        $metadata = $attributes['metadata'] ?? [];
        if (! is_array($metadata)) {
            throw new \InvalidArgumentException('Invoice schedule metadata must be an array.');
        }

        $teamId = $attributes['team_id'] ?? null;
        $customerId = CustomerReference::assertBelongsToTeam($this->database, $attributes['customer_id'] ?? null, $teamId);

        return $this->database->transaction(function () use ($teamId, $customerId, $frequency, $metadata, $attributes): InvoiceSchedule {
            $schedule = InvoiceSchedule::query()->create([
            'team_id' => $teamId,
            'customer_id' => $customerId,
            'frequency' => $frequency,
            'next_run_at' => $attributes['next_run_at'] ?? now(),
            'active' => $attributes['active'] ?? true,
            'metadata' => $metadata,
            ]);
            InvoiceScheduleCreated::dispatch($schedule);

            return $schedule;
        });
    }
}
