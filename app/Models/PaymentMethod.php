<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $customer_id
 * @property int $payment_gateway_id
 * @property string $type
 * @property string $token
 * @property bool $is_default
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Customer $customer
 * @property-read PaymentGateway $paymentGateway
 */
#[Fillable([
    'customer_id',
    'payment_gateway_id',
    'type',
    'token',
    'is_default',
])]
class PaymentMethod extends Model
{
    protected $casts = [
        'is_default' => 'boolean',
        'token' => 'encrypted',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function paymentGateway(): BelongsTo
    {
        return $this->belongsTo(PaymentGateway::class);
    }
}
