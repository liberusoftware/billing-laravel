<?php

declare(strict_types=1);

namespace Liberu\Billing\Orders\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['team_id', 'customer_id', 'currency', 'items', 'expires_at', 'status'])]
class Cart extends Model
{
    protected $table = 'billing_order_carts';

    protected function casts(): array
    {
        return ['items' => 'array', 'expires_at' => 'datetime'];
    }
}
