<?php

declare(strict_types=1);

namespace Liberu\Billing\Payments\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['team_id', 'customer_id', 'type', 'provider', 'provider_reference', 'display_name', 'last_four', 'expires_at', 'is_default', 'metadata'])]
class PaymentMethod extends Model
{
    protected $table = 'billing_payment_methods';

    protected function casts(): array
    {
        return ['expires_at' => 'date', 'is_default' => 'boolean', 'metadata' => 'array'];
    }
}
