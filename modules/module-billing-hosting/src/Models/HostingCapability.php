<?php

declare(strict_types=1);

namespace Liberu\Billing\Hosting\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

final class HostingCapability extends Model
{
    use SoftDeletes;

    protected $table = 'billing_hosting_capabilities';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['configuration' => 'array'];
    }
}
