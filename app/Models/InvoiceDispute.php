<?php

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
 * @property int|null $team_id
 * @property int|null $invoice_id
 * @property int $customer_id
 * @property string $status
 * @property string $reason
 * @property string $description
 * @property string|null $resolution_notes
 * @property Carbon|null $resolved_at
 * @property int|null $resolved_by
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Invoice|null $invoice
 * @property-read Customer|null $customer
 * @property-read User|null $resolver
 * @property-read Team|null $team
 * @property-read Collection<int, DisputeMessage> $messages
 */
#[Fillable([
    'invoice_id',
    'customer_id',
    'status',
    'reason',
    'description',
    'resolution_notes',
    'resolved_at',
    'resolved_by',
])]
class InvoiceDispute extends Model
{
    use HasTeam;

    #[Override]
    protected function casts(): array
    {

        return [
            'resolved_at' => 'datetime',
        ];

    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function resolver(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'resolved_by'
        );
    }

    public function messages(): HasMany
    {
        return $this->hasMany(DisputeMessage::class);
    }
}
