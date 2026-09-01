<?php

declare(strict_types=1);

namespace Liberu\Billing\Orders\Actions;

use Illuminate\Database\DatabaseManager;
use Liberu\Billing\Orders\Events\OrderChangeAdded;
use Liberu\Billing\Orders\Models\Order;

final readonly class AddChangeOrder
{
    public function __construct(private DatabaseManager $database) {}

    /** @param array<string,mixed> $change */
    public function execute(Order $order, array $change): Order
    {
        if (trim((string) ($change['reason'] ?? '')) === '') {
            throw new \InvalidArgumentException('A change order reason is required.');
        }

        return $this->database->transaction(function () use ($order, $change): Order {
            $locked = Order::query()->lockForUpdate()->findOrFail($order->getKey());
            $changes = $locked->change_orders ?? [];
            $changes[] = [...$change, 'created_at' => now()->toIso8601String()];
            $locked->update(['change_orders' => $changes]);
            OrderChangeAdded::dispatch($locked, $change);

            return $locked->refresh();
        });
    }
}
