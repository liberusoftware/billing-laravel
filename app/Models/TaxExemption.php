<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use Override;

/**
 * @property int $id
 * @property int|null $customer_id
 * @property string|null $exemption_number
 * @property string $reason
 * @property Carbon|null $expiry_date
 * @property bool $is_active
 * @property string|null $documentation_path
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Customer|null $customer
 */
#[Fillable([
    'customer_id',
    'exemption_number',
    'reason',
    'expiry_date',
    'is_active',
    'documentation_path',
])]
class TaxExemption extends Model
{
    #[Override]
    protected function casts(): array
    {
        return [
            'expiry_date' => 'date',
            'is_active' => 'boolean',
        ];
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function isValid(): bool
    {
        return $this->is_active &&
            ($this->expiry_date === null || $this->expiry_date->isFuture());
    }
}
