<?php

declare(strict_types=1);

namespace Liberu\Billing\Core\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['team_id', 'name', 'due_days', 'default'])]
class BillingTerm extends Model
{
    protected $table = 'billing_terms';

    protected function casts(): array
    {
        return ['due_days' => 'integer', 'default' => 'boolean'];
    }
}
