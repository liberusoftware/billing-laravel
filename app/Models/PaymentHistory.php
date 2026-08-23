<?php

declare(strict_types=1);

namespace App\Models;

use App\Traits\HasTeam;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int|null $payment_id
 * @property int|null $invoice_id
 * @property int|null $customer_id
 * @property string $amount
 * @property string $currency
 * @property string $payment_method
 * @property string|null $transaction_id
 * @property string $status
 * @property string|null $notes
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Payment|null $payment
 * @property-read Invoice|null $invoice
 * @property-read Customer|null $customer
 */
#[Fillable([
    'payment_id',
    'invoice_id',
    'customer_id',
    'amount',
    'currency',
    'payment_method',
    'transaction_id',
    'status',
    'notes',
])]
class PaymentHistory extends Model
{
    use HasTeam;

    public function payment(): BelongsTo
    {
        return $this->belongsTo(Payment::class);
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }
}
