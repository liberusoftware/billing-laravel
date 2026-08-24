<?php

declare(strict_types=1);

namespace Liberu\Billing\Payments\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Liberu\Billing\Payments\Enums\PaymentStatus;

#[Fillable(['team_id', 'customer_id', 'amount_minor', 'currency', 'status', 'payment_method', 'gateway', 'provider_reference', 'captured_at', 'refunded_minor', 'metadata'])]
class Payment extends Model
{
    protected $table = 'billing_payments';

    protected function casts(): array
    {
        return [
            'amount_minor' => 'integer',
            'refunded_minor' => 'integer',
            'status' => PaymentStatus::class,
            'captured_at' => 'datetime',
            'metadata' => 'array',
        ];
    }

    public function allocations()
    {
        return $this->hasMany(PaymentAllocation::class);
    }
}
