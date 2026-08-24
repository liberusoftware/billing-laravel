<?php

declare(strict_types=1);

namespace Liberu\Billing\Payments\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['payment_id', 'invoice_id', 'amount_minor', 'currency', 'metadata'])]
class PaymentAllocation extends Model
{
    protected $table = 'billing_payment_allocations';

    protected function casts(): array
    {
        return ['amount_minor' => 'integer', 'metadata' => 'array'];
    }

    public function payment()
    {
        return $this->belongsTo(Payment::class);
    }
}
