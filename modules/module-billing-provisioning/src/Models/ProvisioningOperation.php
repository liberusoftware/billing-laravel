<?php

declare(strict_types=1);

namespace Liberu\Billing\Provisioning\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class ProvisioningOperation extends Model
{
    protected $table = 'billing_provisioning_operations';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['attempts' => 'integer', 'next_poll_at' => 'datetime', 'payload' => 'array'];
    }

    public function service(): BelongsTo
    {
        return $this->belongsTo(ProvisionedService::class, 'provisioned_service_id');
    }
}
