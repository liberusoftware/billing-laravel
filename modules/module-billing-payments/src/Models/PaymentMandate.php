<?php

declare(strict_types=1);

namespace Liberu\Billing\Payments\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['team_id', 'customer_id', 'payment_method_id', 'provider', 'provider_reference', 'status', 'metadata'])]
class PaymentMandate extends Model
{
    protected $table = 'billing_payment_mandates';

    protected function casts(): array
    {
        return ['metadata' => 'array'];
    }
}
