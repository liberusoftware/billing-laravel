<?php

declare(strict_types=1);

namespace Liberu\Billing\Core\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['team_id', 'name', 'prefix', 'next_number'])]
class BillingSequence extends Model
{
    protected $table = 'billing_sequences';

    protected function casts(): array
    {
        return ['next_number' => 'integer'];
    }
}
