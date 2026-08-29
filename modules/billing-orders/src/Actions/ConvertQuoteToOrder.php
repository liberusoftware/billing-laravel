<?php

declare(strict_types=1);

namespace Liberu\Billing\Orders\Actions;

use Illuminate\Database\DatabaseManager;
use Illuminate\Support\Str;
use Liberu\Billing\Orders\Enums\FraudReviewStatus;
use Liberu\Billing\Orders\Models\Order;
use Liberu\Billing\Orders\Models\Quote;

final readonly class ConvertQuoteToOrder
{
    public function __construct(private DatabaseManager $database) {}

    public function execute(Quote $quote): Order
    {
        return $this->database->transaction(function () use ($quote): Order {
            $locked = Quote::query()->lockForUpdate()->findOrFail($quote->getKey());
            if ($locked->status !== 'accepted') {
                throw new \LogicException('Only accepted quotes can be converted to orders.');
            }
            $existing = Order::query()->where('quote_id', $locked->getKey())->first();
            if ($existing instanceof Order) {
                return $existing;
            }

            return Order::query()->create(['team_id' => $locked->team_id, 'customer_id' => $locked->customer_id, 'quote_id' => $locked->getKey(), 'order_number' => 'ORD-'.strtoupper(Str::random(10)), 'currency' => $locked->currency, 'subtotal_minor' => $locked->total_minor, 'discount_minor' => 0, 'tax_minor' => 0, 'total_minor' => $locked->total_minor, 'status' => 'draft', 'fraud_status' => FraudReviewStatus::NotRequired, 'metadata' => ['quote_id' => $locked->getKey(), 'items' => $locked->items ?? []]]);
        });
    }
}
