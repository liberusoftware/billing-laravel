<?php

declare(strict_types=1);

namespace Liberu\Billing\Domains\Models;

use Illuminate\Database\Eloquent\Model;

final class EppOperation extends Model
{
    protected $table = 'billing_domain_epp_operations';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['payload' => 'array'];
    }
}
