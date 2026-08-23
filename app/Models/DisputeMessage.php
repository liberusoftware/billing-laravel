<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $invoice_dispute_id
 * @property int $user_id
 * @property string $message
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read InvoiceDispute $invoiceDispute
 * @property-read User $user
 */
#[Fillable([
    'invoice_dispute_id',
    'user_id',
    'message',
    'attachments',
])]
class DisputeMessage extends Model
{
    protected function casts(): array
    {
        return [
            'attachments' => 'array',
        ];
    }

    public function invoiceDispute(): BelongsTo
    {
        return $this->belongsTo(InvoiceDispute::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
