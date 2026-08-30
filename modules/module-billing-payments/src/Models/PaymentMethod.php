<?php

declare(strict_types=1);

namespace Liberu\Billing\Payments\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Liberu\Billing\Payments\Enums\PaymentMethodStatus;

#[Fillable(['team_id', 'customer_id', 'type', 'provider', 'provider_reference', 'display_name', 'last_four', 'expires_at', 'is_default', 'status', 'metadata'])]
class PaymentMethod extends Model
{
    protected $table = 'billing_payment_methods';

    protected function casts(): array
    {
        return ['expires_at' => 'date', 'is_default' => 'boolean', 'status' => PaymentMethodStatus::class, 'metadata' => 'array'];
    }
}
