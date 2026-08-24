<?php

declare(strict_types=1);

namespace Liberu\Billing\Orders\Actions;

use Illuminate\Database\DatabaseManager;
use Liberu\Billing\Orders\Enums\FraudReviewStatus;
use Liberu\Billing\Orders\Models\Order;

final readonly class ReviewFraud
{
    public function __construct(private DatabaseManager $database) {}

    public function execute(Order $order, FraudReviewStatus $status): Order
    {
        return $this->database->transaction(function () use ($order, $status): Order {
            $order->update(['fraud_status' => $status]);

            return $order->refresh();
        });
    }
}
