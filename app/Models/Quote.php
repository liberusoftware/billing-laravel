<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Override;

/**
 * @property int $id
 * @property int|null $team_id
 * @property int $customer_id
 * @property string $quote_number
 * @property string $title
 * @property string $status
 * @property Carbon|null $valid_until
 * @property string $subtotal
 * @property string $tax_amount
 * @property string $total
 * @property string $currency
 * @property string|null $notes
 * @property string|null $terms
 * @property Carbon|null $sent_at
 * @property Carbon|null $viewed_at
 * @property Carbon|null $accepted_at
 * @property Carbon|null $declined_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Team|null $team
 * @property-read Customer|null $customer
 * @property-read Collection<int, QuoteItem> $items
 */
#[Fillable([
    'team_id',
    'customer_id',
    'quote_number',
    'title',
    'status',
    'valid_until',
    'subtotal',
    'tax_amount',
    'total',
    'currency',
    'notes',
    'terms',
    'sent_at',
    'viewed_at',
    'accepted_at',
    'declined_at',
])]
class Quote extends Model
{
    #[Override]
    protected function casts(): array
    {

        return [
            'valid_until' => 'date',
            'subtotal' => 'decimal:2',
            'tax_amount' => 'decimal:2',
            'total' => 'decimal:2',
            'sent_at' => 'datetime',
            'viewed_at' => 'datetime',
            'accepted_at' => 'datetime',
            'declined_at' => 'datetime',
        ];

    }

    #[Override]
    protected static function boot(): void
    {
        parent::boot();

        static::creating(
            static function (Quote $quote): void {
                if (empty($quote->quote_number)) {
                    $quote->quote_number = 'QUO-'.strtoupper(Str::random(8));
                }
            }
        );
    }

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(QuoteItem::class)->orderBy('sort_order');
    }

    public function isExpired(): bool
    {
        return $this->valid_until && $this->valid_until->isPast() && $this->status !== 'accepted';
    }

    public function canBeConverted(): bool
    {
        return $this->status === 'accepted';
    }
}
