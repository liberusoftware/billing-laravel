<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'reseller_agreement_id', 'reseller_service_delegation_id', 'invoice_id',
    'gross_amount', 'provider_amount', 'reseller_amount', 'currency',
    'status', 'settled_at',
])]
class ResellerRevenueTransaction extends Model
{
    protected function casts(): array
    {
        return [
            'gross_amount' => 'decimal:2',
            'provider_amount' => 'decimal:2',
            'reseller_amount' => 'decimal:2',
            'settled_at' => 'datetime',
        ];
    }

    public function agreement(): BelongsTo
    {
        return $this->belongsTo(ResellerAgreement::class, 'reseller_agreement_id');
    }

    public function delegation(): BelongsTo
    {
        return $this->belongsTo(ResellerServiceDelegation::class, 'reseller_service_delegation_id');
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }
}
