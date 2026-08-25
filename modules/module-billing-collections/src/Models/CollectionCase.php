<?php

declare(strict_types=1);

namespace Liberu\Billing\Collections\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Liberu\Billing\Collections\Enums\CollectionStatus;

#[Fillable(['team_id', 'customer_id', 'invoice_id', 'type', 'status', 'amount_minor', 'currency', 'next_action_at', 'promise_due_at', 'reason', 'metadata'])]
class CollectionCase extends Model
{
    protected $table = 'billing_collection_cases';

    protected function casts(): array
    {
        return ['status' => CollectionStatus::class, 'amount_minor' => 'integer', 'next_action_at' => 'datetime', 'promise_due_at' => 'datetime', 'metadata' => 'array'];
    }
}
