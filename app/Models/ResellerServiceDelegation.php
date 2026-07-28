<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property int $reseller_agreement_id
 * @property int $subscription_id
 * @property string $wholesale_price
 * @property string $retail_price
 * @property string $currency
 * @property string $status
 * @property-read ResellerAgreement $agreement
 */
#[Fillable([
    'reseller_agreement_id', 'subscription_id', 'wholesale_price',
    'retail_price', 'currency', 'status',
])]
class ResellerServiceDelegation extends Model
{
    protected function casts(): array
    {
        return ['wholesale_price' => 'decimal:2', 'retail_price' => 'decimal:2'];
    }

    public function agreement(): BelongsTo
    {
        return $this->belongsTo(ResellerAgreement::class, 'reseller_agreement_id');
    }

    public function subscription(): BelongsTo
    {
        return $this->belongsTo(Subscription::class);
    }

    public function revenueTransactions(): HasMany
    {
        return $this->hasMany(ResellerRevenueTransaction::class);
    }
}
