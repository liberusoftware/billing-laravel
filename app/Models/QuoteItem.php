<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use Override;

/**
 * @property int $id
 * @property int $quote_id
 * @property string $description
 * @property numeric-string $quantity
 * @property numeric-string $unit_price
 * @property numeric-string $total
 * @property int $sort_order
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Quote|null $quote
 */
#[Fillable([
    'quote_id',
    'description',
    'quantity',
    'unit_price',
    'total',
    'sort_order',
])]
class QuoteItem extends Model
{
    #[Override]
    protected function casts(): array
    {

        return [
            'quantity' => 'decimal:2',
            'unit_price' => 'decimal:2',
            'total' => 'decimal:2',
        ];

    }

    #[Override]
    protected static function boot(): void
    {
        parent::boot();

        static::saving(
            static function (QuoteItem $item): void {
                $item->total = (string) ($item->quantity * $item->unit_price);
            }
        );
    }

    public function quote(): BelongsTo
    {
        return $this->belongsTo(Quote::class);
    }
}
