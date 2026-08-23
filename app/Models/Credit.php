<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use Override;

/**
 * @property int $id
 * @property int $customer_id
 * @property string $amount
 * @property string|null $description
 * @property Carbon|null $expiry_date
 * @property int|null $team_id
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Customer|null $customer
 */
#[Fillable([
    'customer_id',
    'amount',
    'description',
    'expiry_date',
])]
class Credit extends Model
{
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    #[Override]
    protected function casts(): array
    {
        return ['expiry_date' => 'datetime'];
    }
}
