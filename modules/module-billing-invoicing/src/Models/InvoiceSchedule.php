<?php

declare(strict_types=1);

namespace Liberu\Billing\Invoicing\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['team_id', 'customer_id', 'frequency', 'next_run_at', 'active', 'metadata'])]
class InvoiceSchedule extends Model
{
    protected $table = 'billing_invoice_schedules';

    protected function casts(): array
    {
        return ['next_run_at' => 'datetime', 'active' => 'boolean', 'metadata' => 'array'];
    }
}
