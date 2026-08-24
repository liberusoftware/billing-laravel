<?php

declare(strict_types=1);

namespace Liberu\Billing\Isp\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

final class IspCapability extends Model
{
    use SoftDeletes;

    protected $table = 'billing_isp_capabilities';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['configuration' => 'array'];
    }
}
