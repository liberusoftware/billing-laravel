<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SubscriptionChange extends Model
{
    protected $fillable = ['subscription_id', 'customer_id', 'invoice_id', 'target_product_service_id', 'type', 'status', 'last_error', 'completed_at'];

    protected function casts(): array
    {
        return ['completed_at' => 'datetime'];
    }

    /** @return BelongsTo<Subscription, $this> */
    public function subscription(): BelongsTo
    {
        return $this->belongsTo(Subscription::class);
    }

    /** @return BelongsTo<Invoice, $this> */
    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    /** @return BelongsTo<Products_Service, $this> */
    public function targetProduct(): BelongsTo
    {
        return $this->belongsTo(Products_Service::class, 'target_product_service_id');
    }
}
