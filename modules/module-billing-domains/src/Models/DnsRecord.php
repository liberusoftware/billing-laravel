<?php

declare(strict_types=1);

namespace Liberu\Billing\Domains\Models;

use Illuminate\Database\Eloquent\Model;

final class DnsRecord extends Model
{
    protected $table = 'billing_domain_dns_records';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['ttl' => 'integer'];
    }
}
