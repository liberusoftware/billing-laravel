<?php

declare(strict_types=1);

namespace Liberu\Billing\Core\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['team_id', 'name', 'email', 'phone', 'metadata'])]
class BillingContact extends Model
{
    protected $table = 'billing_contacts';

    protected function casts(): array
    {
        return ['metadata' => 'array'];
    }
}
