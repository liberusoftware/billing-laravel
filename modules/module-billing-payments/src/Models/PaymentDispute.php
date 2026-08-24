<?php

declare(strict_types=1);

namespace Liberu\Billing\Payments\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Liberu\Billing\Payments\Enums\DisputeStatus;

#[Fillable(['payment_id', 'amount_minor', 'status', 'provider_reference', 'reason', 'evidence', 'metadata'])]
class PaymentDispute extends Model
{
    protected $table = 'billing_payment_disputes';

    protected function casts(): array
    {
        return ['amount_minor' => 'integer', 'status' => DisputeStatus::class, 'evidence' => 'array', 'metadata' => 'array'];
    }

    public function payment(): BelongsTo
    {
        return $this->belongsTo(Payment::class);
    }
}
