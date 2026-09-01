<?php

declare(strict_types=1);

namespace Liberu\Billing\Orders\Actions;

use Illuminate\Database\DatabaseManager;
use Liberu\Billing\Orders\Enums\FraudReviewStatus;
use Liberu\Billing\Orders\Enums\OrderStatus;
use Liberu\Billing\Orders\Events\OrderFraudReviewed;
use Liberu\Billing\Orders\Models\Order;

final readonly class ReviewFraud
{
    public function __construct(private DatabaseManager $database) {}

    public function execute(Order $order, FraudReviewStatus $status): Order
    {
        return $this->database->transaction(function () use ($order, $status): Order {
            $locked = Order::query()->lockForUpdate()->findOrFail($order->getKey());
            if (in_array($locked->status, [OrderStatus::Completed, OrderStatus::Cancelled], true)) {
                throw new \LogicException('Terminal orders cannot receive fraud reviews.');
            }

            $locked->update(['fraud_status' => $status]);
            OrderFraudReviewed::dispatch($locked, $status->value);

            return $locked->refresh();
        });
    }
}
