<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Carbon;
use Override;

/**
 * @property int $id
 * @property int|null $order_form_template_id
 * @property int $customer_id
 * @property int|null $subscription_id
 * @property int|null $invoice_id
 * @property string|null $checkout_key
 * @property string|null $checkout_fingerprint
 * @property string $status
 * @property array|null $submitted_data
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read OrderFormTemplate|null $template
 * @property-read Customer|null $customer
 * @property-read Subscription|null $subscription
 * @property-read HostingFulfillment|null $fulfillment
 * @property-read Invoice|null $invoice
 */
#[Fillable([
    'order_form_template_id',
    'customer_id',
    'subscription_id',
    'invoice_id',
    'status',
    'submitted_data',
    'checkout_key',
    'checkout_fingerprint',
])]
class Order extends Model
{
    use HasFactory;

    #[Override]
    protected function casts(): array
    {
        return [
            'submitted_data' => 'array',
        ];
    }

    public function fulfillment(): HasOne
    {
        return $this->hasOne(HostingFulfillment::class);
    }

    public function template(): BelongsTo
    {
        return $this->belongsTo(OrderFormTemplate::class, 'order_form_template_id');
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function subscription(): BelongsTo
    {
        return $this->belongsTo(Subscription::class);
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }
}
