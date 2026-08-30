<?php

declare(strict_types=1);

namespace Liberu\Billing\CustomerPortal\Actions;

use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Liberu\Billing\CustomerPortal\Events\PortalItemCreated;
use Liberu\Billing\CustomerPortal\Models\PortalItem;
use Liberu\Billing\CustomerPortal\Support\CustomerReference;

final class CreatePortalItem
{
    /** @param array<string,mixed> $attributes */
    public function handle(int $teamId, array $attributes): PortalItem
    {
        $type = (string) ($attributes['type'] ?? '');
        $subject = trim((string) ($attributes['subject'] ?? ''));
        if ($teamId < 1 || ! in_array($type, ['profile', 'orders', 'services', 'usage', 'invoices', 'payments', 'tickets', 'changes', 'cancellation'], true) || $subject === '') {
            throw new InvalidArgumentException('Portal item type and subject are invalid.');
        }

        $customerId = CustomerReference::assertBelongsToTeam(app('db'), $attributes['customer_id'] ?? null, $teamId);

        return DB::transaction(function () use ($teamId, $customerId, $type, $subject, $attributes): PortalItem {
            $item = PortalItem::query()->create(['team_id' => $teamId, 'customer_id' => $customerId, 'type' => $type, 'status' => 'open', 'subject' => $subject, 'payload' => $attributes['payload'] ?? []]);
            PortalItemCreated::dispatch($item);

            return $item;
        });
    }
}
