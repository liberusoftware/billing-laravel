<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\VoipAccountStatus;
use App\Enums\VoipPlatform;
use App\Traits\HasTeam;
use Database\Factories\VoipAccountFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $team_id
 * @property int $customer_id
 * @property int|null $subscription_id
 * @property VoipPlatform $platform
 * @property VoipAccountStatus $status
 * @property string $sip_username
 * @property string $sip_secret
 * @property string|null $caller_id
 * @property string|null $credit_limit
 * @property string $current_usage_cost
 * @property int $max_concurrent_calls
 * @property bool $international_enabled
 * @property Carbon|null $provisioned_at
 * @property Carbon|null $platform_synced_at
 */
#[Fillable([
    'team_id', 'customer_id', 'subscription_id', 'platform', 'status',
    'sip_username', 'sip_secret', 'caller_id', 'credit_limit',
    'current_usage_cost', 'max_concurrent_calls', 'international_enabled',
    'provisioned_at', 'platform_synced_at',
])]
class VoipAccount extends Model
{
    use HasFactory;
    use HasTeam;

    protected $hidden = ['sip_secret'];

    protected static function newFactory(): VoipAccountFactory
    {
        return VoipAccountFactory::new();
    }

    protected function casts(): array
    {
        return [
            'platform' => VoipPlatform::class,
            'status' => VoipAccountStatus::class,
            'sip_secret' => 'encrypted',
            'credit_limit' => 'decimal:4',
            'current_usage_cost' => 'decimal:4',
            'max_concurrent_calls' => 'integer',
            'international_enabled' => 'boolean',
            'provisioned_at' => 'datetime',
            'platform_synced_at' => 'datetime',
        ];
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function subscription(): BelongsTo
    {
        return $this->belongsTo(Subscription::class);
    }

    public function didNumbers(): HasMany
    {
        return $this->hasMany(DidNumber::class);
    }

    public function callDetailRecords(): HasMany
    {
        return $this->hasMany(CallDetailRecord::class);
    }

    public function fraudAlerts(): HasMany
    {
        return $this->hasMany(VoipFraudAlert::class);
    }
}
