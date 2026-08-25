<?php

declare(strict_types=1);

namespace Liberu\Billing\CustomerPortal\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

final class PortalItem extends Model
{
    use SoftDeletes;

    protected $table = 'billing_customer_portal_items';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['payload' => 'array'];
    }
}
