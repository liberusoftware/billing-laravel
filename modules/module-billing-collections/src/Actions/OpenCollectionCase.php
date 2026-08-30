<?php

declare(strict_types=1);

namespace Liberu\Billing\Collections\Actions;

use Illuminate\Database\DatabaseManager;
use Illuminate\Support\Facades\Schema;
use Liberu\Billing\Collections\Enums\CollectionStatus;
use Liberu\Billing\Collections\Events\CollectionCaseOpened;
use Liberu\Billing\Collections\Models\CollectionCase;
use Liberu\Billing\Collections\Support\CustomerReference;

final readonly class OpenCollectionCase
{
    public function __construct(private DatabaseManager $database) {}

    public function execute(array $attributes): CollectionCase
    {
        $amount = (int) ($attributes['amount_minor'] ?? 0);
        $currency = strtoupper((string) ($attributes['currency'] ?? ''));
        $type = strtolower(trim((string) ($attributes['type'] ?? 'dunning')));
        if ($amount < 1 || ! preg_match('/^[A-Z]{3}$/', $currency)) {
            throw new \InvalidArgumentException('Collection amount and currency are invalid.');
        }
        if (! in_array($type, ['retry', 'dunning', 'reminder', 'promise', 'credit_control', 'suspension', 'write_off', 'recovery'], true)) {
            throw new \InvalidArgumentException('Collection case type is invalid.');
        }

        $teamId = $attributes['team_id'] ?? null;
        $customerId = CustomerReference::assertBelongsToTeam($this->database, $attributes['customer_id'] ?? null, $teamId);

        $invoiceId = $attributes['invoice_id'] ?? null;
        if ($invoiceId !== null && Schema::hasTable('billing_invoices')) {
            $invoiceTeam = $this->database->table('billing_invoices')->where('id', (int) $invoiceId)->value('team_id');
            if ($invoiceTeam === null || (int) $invoiceTeam !== (int) ($attributes['team_id'] ?? 0)) {
                throw new \InvalidArgumentException('Collection invoice reference is invalid.');
            }
        } elseif ($invoiceId !== null) {
            throw new \InvalidArgumentException('Collection invoice reference is invalid.');
        }

        return $this->database->transaction(function () use ($teamId, $customerId, $invoiceId, $type, $amount, $currency, $attributes): CollectionCase {
            $case = CollectionCase::query()->create([
                'team_id' => $teamId, 'customer_id' => $customerId, 'invoice_id' => $invoiceId,
                'type' => $type, 'status' => CollectionStatus::Open, 'amount_minor' => $amount, 'currency' => $currency,
                'next_action_at' => $attributes['next_action_at'] ?? now(), 'reason' => $attributes['reason'] ?? null, 'metadata' => $attributes['metadata'] ?? [],
            ]);
            CollectionCaseOpened::dispatch($case);

            return $case;
        });
    }
}
