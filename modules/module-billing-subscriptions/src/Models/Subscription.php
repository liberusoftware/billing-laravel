<?php

declare(strict_types=1);

namespace Liberu\Billing\Subscriptions\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Liberu\Billing\Subscriptions\Enums\SubscriptionStatus;

#[Fillable(['team_id', 'customer_id', 'pricing_plan_id', 'status', 'starts_at', 'trial_ends_at', 'current_period_ends_at', 'period_days', 'cancelled_at', 'paused_at', 'auto_renew', 'id_protection', 'entitlement_state', 'metadata'])]
class Subscription extends Model
{
    protected $table = 'billing_subscriptions';

    protected function casts(): array
    {
        return [
            'status' => SubscriptionStatus::class,
            'starts_at' => 'datetime',
            'trial_ends_at' => 'datetime',
            'current_period_ends_at' => 'datetime',
            'period_days' => 'integer',
            'cancelled_at' => 'datetime',
            'paused_at' => 'datetime',
            'auto_renew' => 'boolean',
            'id_protection' => 'boolean',
            'entitlement_state' => 'array',
            'metadata' => 'array',
        ];
    }
}
