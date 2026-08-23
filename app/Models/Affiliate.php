<?php

declare(strict_types=1);

namespace App\Models;

use App\Traits\HasTeam;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use Override;

/**
 * @property int $id
 * @property int $user_id
 * @property string $code
 * @property string $commission_rate
 * @property string $status
 * @property array|null $custom_rates
 * @property string $total_earnings
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property int|null $team_id
 * @property-read User|null $user
 * @property-read Collection<int, User> $referrals
 * @property-read Collection<int, Payment> $payments
 * @property-read Collection<int, AffiliateTransaction> $transactions
 */
#[Fillable([
    'user_id',
    'code',
    'commission_rate',
    'status',
    'custom_rates',
])]
class Affiliate extends Model
{
    use HasTeam;

    #[Override]
    protected function casts(): array
    {

        return [
            'custom_rates' => 'array',
        ];

    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function referrals(): HasMany
    {
        return $this->hasMany(
            User::class,
            'referred_by'
        );
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(AffiliateTransaction::class);
    }

    public function getCommissionRate($productId = null, $categoryId = null)
    {
        if ($productId && isset($this->custom_rates['products'][$productId])) {
            return $this->custom_rates['products'][$productId];
        }

        if ($categoryId && isset($this->custom_rates['categories'][$categoryId])) {
            return $this->custom_rates['categories'][$categoryId];
        }

        return $this->commission_rate;
    }
}
