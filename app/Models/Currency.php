<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $code
 * @property string $name
 * @property string|null $symbol
 * @property string $exchange_rate
 * @property bool $is_enabled
 * @property bool $is_base
 * @property int $decimal_precision
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Collection<int, Invoice> $invoices
 * @property-read Collection<int, Invoice_Item> $invoiceItems
 * @property-read Collection<int, Payment> $payments
 */
#[Fillable([
    'code',
    'name',
    'symbol',
    'exchange_rate',
    'is_enabled',
    'is_base',
    'decimal_precision',
])]
class Currency extends Model
{
    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_enabled' => 'boolean',
            'is_base' => 'boolean',
            'decimal_precision' => 'integer',
        ];
    }

    /**
     * Enforce a single base currency: turning is_base on for one row clears it on
     * every other. Query-builder update (not model events) so it does not recurse
     * through this saving hook. Mirrors Team::is_default_for_registration.
     */
    protected static function booted(): void
    {
        static::saving(function (Currency $currency): void {
            if ($currency->is_base && $currency->isDirty('is_base')) {
                static::query()
                    ->where('is_base', true)
                    ->when($currency->exists, fn (Builder $query): Builder => $query->whereKeyNot($currency->getKey()))
                    ->update(['is_base' => false]);
            }
        });
    }

    /**
     * The configured base currency, if one is marked.
     */
    public static function base(): ?self
    {
        return static::query()->where('is_base', true)->first();
    }

    /**
     * @param  Builder<Currency>  $query
     * @return Builder<Currency>
     */
    public function scopeEnabled(Builder $query): Builder
    {
        return $query->where('is_enabled', true);
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(
            Invoice::class,
            'currency',
            'code'
        );
    }

    public function invoiceItems(): HasMany
    {
        return $this->hasMany(
            Invoice_Item::class,
            'currency',
            'code'
        );
    }

    public function payments(): HasMany
    {
        return $this->hasMany(
            Payment::class,
            'currency',
            'code'
        );
    }
}
