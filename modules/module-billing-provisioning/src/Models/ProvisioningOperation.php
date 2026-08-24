<?php

declare(strict_types=1);

namespace Liberu\Billing\Provisioning\Models;

use Illuminate\Database\Eloquent\Model;

final class ProvisioningOperation extends Model
{
    protected $table = 'billing_provisioning_operations';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['attempts' => 'integer', 'next_poll_at' => 'datetime', 'payload' => 'array'];
    }
}
