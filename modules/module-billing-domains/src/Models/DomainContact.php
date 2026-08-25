<?php

declare(strict_types=1);

namespace Liberu\Billing\Domains\Models;

use Illuminate\Database\Eloquent\Model;

final class DomainContact extends Model
{
    protected $table = 'billing_domain_contacts';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['details' => 'array'];
    }
}
