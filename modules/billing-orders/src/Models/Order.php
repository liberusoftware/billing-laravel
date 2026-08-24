<?php

declare(strict_types=1);

namespace Liberu\Billing\Orders\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Liberu\Billing\Orders\Enums\FraudReviewStatus;
use Liberu\Billing\Orders\Enums\OrderStatus;

#[Fillable(['team_id', 'customer_id', 'quote_id', 'order_number', 'currency', 'subtotal_minor', 'discount_minor', 'tax_minor', 'total_minor', 'status', 'fraud_status', 'agreement', 'change_orders', 'metadata'])]
class Order extends Model
{
    protected $table = 'billing_orders';

    protected function casts(): array
    {
        return ['subtotal_minor' => 'integer', 'discount_minor' => 'integer', 'tax_minor' => 'integer', 'total_minor' => 'integer', 'status' => OrderStatus::class, 'fraud_status' => FraudReviewStatus::class, 'agreement' => 'array', 'change_orders' => 'array', 'metadata' => 'array'];
    }
}
