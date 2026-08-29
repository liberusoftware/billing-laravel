<?php

declare(strict_types=1);

namespace Liberu\Billing\Invoicing\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['team_id', 'invoice_id', 'customer_id', 'total_installments', 'installment_amount_minor', 'frequency', 'start_at', 'next_due_at', 'generated_installments', 'status', 'metadata'])]
final class PaymentPlan extends Model
{
    protected $table = 'billing_payment_plans';

    protected function casts(): array
    {
        return ['start_at' => 'datetime', 'next_due_at' => 'datetime', 'total_installments' => 'integer', 'installment_amount_minor' => 'integer', 'generated_installments' => 'integer', 'metadata' => 'array'];
    }

    public function invoice()
    {
        return $this->belongsTo(Invoice::class);
    }
}
