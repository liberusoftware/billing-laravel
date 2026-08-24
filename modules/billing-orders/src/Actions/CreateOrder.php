<?php

declare(strict_types=1);

namespace Liberu\Billing\Orders\Actions;

use Illuminate\Database\DatabaseManager;
use Liberu\Billing\Orders\Enums\FraudReviewStatus;
use Liberu\Billing\Orders\Enums\OrderStatus;
use Liberu\Billing\Orders\Models\Order;

final readonly class CreateOrder
{
    public function __construct(private DatabaseManager $database) {}

    /** @param array<string,mixed> $attributes */
    public function execute(array $attributes): Order
    {
        $subtotal = (int) ($attributes['subtotal_minor'] ?? 0);
        $discount = (int) ($attributes['discount_minor'] ?? 0);
        $tax = (int) ($attributes['tax_minor'] ?? 0);
        if ($subtotal < 0 || $discount < 0 || $tax < 0 || $discount > $subtotal) {
            throw new \InvalidArgumentException('Order amounts are invalid.');
        }
        $review = (bool) ($attributes['fraud_review_required'] ?? false);

        return $this->database->transaction(fn (): Order => Order::query()->create(['team_id' => $attributes['team_id'] ?? null, 'customer_id' => $attributes['customer_id'] ?? null, 'quote_id' => $attributes['quote_id'] ?? null, 'order_number' => $attributes['order_number'] ?? ('ORD-'.strtoupper(bin2hex(random_bytes(5)))), 'currency' => strtoupper((string) $attributes['currency']), 'subtotal_minor' => $subtotal, 'discount_minor' => $discount, 'tax_minor' => $tax, 'total_minor' => $subtotal - $discount + $tax, 'status' => $review ? OrderStatus::PendingReview : OrderStatus::Approved, 'fraud_status' => $review ? FraudReviewStatus::Pending : FraudReviewStatus::NotRequired, 'agreement' => $attributes['agreement'] ?? null, 'change_orders' => [], 'metadata' => $attributes['metadata'] ?? []]));
    }
}
