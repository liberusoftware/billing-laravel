<?php

declare(strict_types=1);

namespace Liberu\Billing\Core\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['team_id', 'values'])]
class BillingSetting extends Model
{
    protected $table = 'billing_settings';

    protected function casts(): array
    {
        return ['values' => 'array'];
    }
}
