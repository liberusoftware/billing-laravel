<?php

declare(strict_types=1);

namespace Liberu\Billing\Payments\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Liberu\Billing\Payments\Enums\ReconciliationStatus;

#[Fillable(['payment_id', 'status', 'provider_reference', 'notes', 'metadata'])]
class PaymentReconciliation extends Model
{
    protected $table = 'billing_payment_reconciliations';

    protected function casts(): array
    {
        return ['status' => ReconciliationStatus::class, 'metadata' => 'array'];
    }
}
