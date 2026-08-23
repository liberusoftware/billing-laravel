<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use Override;

/**
 * @property int $id
 * @property int $subscription_id
 * @property int|null $invoice_id
 * @property string $reason
 * @property string|null $notes
 * @property Carbon $suspended_at
 * @property Carbon|null $unsuspended_at
 * @property int|null $suspended_by
 * @property int|null $unsuspended_by
 * @property bool $is_active
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Subscription|null $subscription
 * @property-read Invoice|null $invoice
 * @property-read User|null $suspendedBy
 * @property-read User|null $unsuspendedBy
 */
#[Fillable([
    'subscription_id',
    'invoice_id',
    'reason',
    'notes',
    'suspended_at',
    'unsuspended_at',
    'suspended_by',
    'unsuspended_by',
    'is_active',
])]
class ServiceSuspension extends Model
{
    #[Override]
    protected function casts(): array
    {
        return [
            'suspended_at' => 'datetime',
            'unsuspended_at' => 'datetime',
            'is_active' => 'boolean',
        ];
    }

    public function subscription(): BelongsTo
    {
        return $this->belongsTo(Subscription::class);
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    public function suspendedBy(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'suspended_by'
        );
    }

    public function unsuspendedBy(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'unsuspended_by'
        );
    }

    public function unsuspend(?int $userId = null): void
    {
        $this->update(
            [
                'unsuspended_at' => now(),
                'unsuspended_by' => $userId,
                'is_active' => false,
            ]
        );
    }

    public function isSuspended(): bool
    {
        return $this->is_active && $this->unsuspended_at === null;
    }
}
