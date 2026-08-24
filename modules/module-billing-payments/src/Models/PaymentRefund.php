<?php

declare(strict_types=1);

namespace Liberu\Billing\Payments\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Liberu\Billing\Payments\Enums\RefundStatus;

#[Fillable(['payment_id', 'amount_minor', 'status', 'provider_reference', 'reason', 'metadata'])]
class PaymentRefund extends Model
{
    protected $table = 'billing_payment_refunds';

    protected function casts(): array
    {
        return ['amount_minor' => 'integer', 'status' => RefundStatus::class, 'metadata' => 'array'];
    }
}
