<?php

declare(strict_types=1);

namespace Liberu\Billing\Invoicing\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['invoice_id', 'description', 'quantity', 'unit_amount_minor', 'tax_rate', 'amount_minor', 'metadata'])]
class InvoiceLine extends Model
{
    protected $table = 'billing_invoice_lines';

    protected function casts(): array
    {
        return ['quantity' => 'decimal:4', 'unit_amount_minor' => 'integer', 'tax_rate' => 'decimal:4', 'amount_minor' => 'integer', 'metadata' => 'array'];
    }
}
