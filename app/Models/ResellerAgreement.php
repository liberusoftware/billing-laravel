<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property int $provider_team_id
 * @property int $reseller_team_id
 * @property string $default_discount_percent
 * @property string $revenue_share_percent
 * @property string|null $credit_limit
 * @property string $credit_used
 * @property string $currency
 * @property string $status
 * @property array<int|string, array{price?: float|int|string, discount_percent?: float|int|string}>|null $product_pricing
 */
#[Fillable([
    'provider_team_id', 'reseller_team_id', 'default_discount_percent',
    'revenue_share_percent', 'credit_limit', 'credit_used', 'currency',
    'status', 'product_pricing',
])]
class ResellerAgreement extends Model
{
    protected function casts(): array
    {
        return [
            'default_discount_percent' => 'decimal:2',
            'revenue_share_percent' => 'decimal:2',
            'credit_limit' => 'decimal:2',
            'credit_used' => 'decimal:2',
            'product_pricing' => 'array',
        ];
    }

    public function providerTeam(): BelongsTo
    {
        return $this->belongsTo(Team::class, 'provider_team_id');
    }

    public function resellerTeam(): BelongsTo
    {
        return $this->belongsTo(Team::class, 'reseller_team_id');
    }

    /** @return HasMany<ResellerServiceDelegation, $this> */
    public function delegations(): HasMany
    {
        return $this->hasMany(ResellerServiceDelegation::class);
    }

    /** @return HasMany<ResellerRevenueTransaction, $this> */
    public function revenueTransactions(): HasMany
    {
        return $this->hasMany(ResellerRevenueTransaction::class);
    }
}
