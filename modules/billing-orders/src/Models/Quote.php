<?php

declare(strict_types=1);

namespace Liberu\Billing\Orders\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['team_id', 'customer_id', 'quote_number', 'currency', 'total_minor', 'items', 'valid_until', 'status'])]
class Quote extends Model
{
    protected $table = 'billing_order_quotes';

    protected function casts(): array
    {
        return ['total_minor' => 'integer', 'items' => 'array', 'valid_until' => 'datetime'];
    }
}
