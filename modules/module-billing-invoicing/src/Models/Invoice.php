<?php

declare(strict_types=1);

namespace Liberu\Billing\Invoicing\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Liberu\Billing\Invoicing\Enums\InvoiceStatus;

#[Fillable(['team_id', 'customer_id', 'number', 'status', 'currency', 'subtotal_minor', 'tax_minor', 'total_minor', 'due_at', 'finalized_at', 'metadata'])]
class Invoice extends Model
{
    protected $table = 'billing_invoices';

    protected function casts(): array
    {
        return ['status' => InvoiceStatus::class, 'subtotal_minor' => 'integer', 'tax_minor' => 'integer', 'total_minor' => 'integer', 'due_at' => 'datetime', 'finalized_at' => 'datetime', 'metadata' => 'array'];
    }

    public function lines()
    {
        return $this->hasMany(InvoiceLine::class);
    }
}
