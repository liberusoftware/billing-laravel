<?php

declare(strict_types=1);

namespace Liberu\Billing\Orders\Actions;

use Illuminate\Database\DatabaseManager;
use Liberu\Billing\Orders\Enums\FraudReviewStatus;
use Liberu\Billing\Orders\Enums\OrderStatus;
use Liberu\Billing\Orders\Models\Order;

final readonly class TransitionOrder
{
    public function __construct(private DatabaseManager $database) {}

    public function execute(Order $order, OrderStatus $status, ?FraudReviewStatus $fraudStatus = null): Order
    {
        if ($order->status === OrderStatus::Completed || $order->status === OrderStatus::Cancelled) {
            throw new \LogicException('Terminal orders cannot transition.');
        }
        if ($status === OrderStatus::Approved && $order->fraud_status === FraudReviewStatus::Blocked) {
            throw new \LogicException('Blocked orders cannot be approved.');
        }

        return $this->database->transaction(function () use ($order, $status, $fraudStatus): Order {
            $order->update(['status' => $status, 'fraud_status' => $fraudStatus ?? $order->fraud_status]);

            return $order->refresh();
        });
    }
}
