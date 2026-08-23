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
 * @property int|null $invoice_id
 * @property int $total_installments
 * @property string $installment_amount
 * @property string $frequency
 * @property Carbon|null $start_date
 * @property Carbon|null $next_due_date
 * @property string $status
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Invoice|null $invoice
 * @property-read Collection<int, Invoice> $installments
 * @property-read Team|null $team
 */
#[Fillable([
    'invoice_id',
    'total_installments',
    'installment_amount',
    'frequency',
    'start_date',
    'next_due_date',
    'status',
])]
class PaymentPlan extends Model
{
    use HasTeam;

    #[Override]
    protected function casts(): array
    {
        return [
            'start_date' => 'datetime',
            'next_due_date' => 'datetime',
        ];
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    public function installments(): HasMany
    {
        // Installment invoices reference the plan's parent invoice, so match
        // child.parent_invoice_id against this plan's invoice_id (not its id).
        return $this->hasMany(
            Invoice::class,
            'parent_invoice_id',
            'invoice_id'
        );
    }
}
