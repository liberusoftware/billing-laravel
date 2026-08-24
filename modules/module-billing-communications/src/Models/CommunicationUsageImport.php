<?php

declare(strict_types=1);

namespace Liberu\Billing\Communications\Models;

use Illuminate\Database\Eloquent\Model;

final class CommunicationUsageImport extends Model
{
    protected $table = 'billing_communication_usage_imports';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['metadata' => 'array', 'total_amount_minor' => 'integer', 'rows' => 'integer'];
    }
}
