<?php

declare(strict_types=1);

namespace Liberu\Billing\Invoicing\Models;

use Illuminate\Database\Eloquent\Model;

final class InvoiceSupport extends Model
{
    protected $table = 'billing_invoice_support';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['amount_minor' => 'integer', 'payload' => 'array'];
    }
}
