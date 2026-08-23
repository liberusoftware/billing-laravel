<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\BroadbandTechnology;
use App\Enums\IspServiceStatus;
use App\Enums\RadiusPlatform;
use App\Traits\HasTeam;
use Database\Factories\IspServiceFactory;
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
 * @property int|null $product_service_id
 * @property BroadbandTechnology $technology
 * @property IspServiceStatus $status
 * @property RadiusPlatform $radius_platform
 * @property string $radius_username
 * @property string $radius_secret
 * @property int|null $download_limit_bps
 * @property int|null $upload_limit_bps
 * @property int|null $monthly_data_limit_bytes
 * @property int $current_period_usage_bytes
 * @property Carbon|null $activated_at
 * @property Carbon|null $suspended_at
 * @property string|null $suspension_reason
 * @property Carbon|null $radius_synced_at
 */
#[Fillable([
    'team_id',
    'customer_id',
    'subscription_id',
    'product_service_id',
    'technology',
    'status',
    'radius_platform',
    'radius_username',
    'radius_secret',
    'download_limit_bps',
    'upload_limit_bps',
    'monthly_data_limit_bytes',
    'current_period_usage_bytes',
    'activated_at',
    'suspended_at',
    'suspension_reason',
    'radius_synced_at',
])]
class IspService extends Model
{
    use HasFactory;
    use HasTeam;

    protected $hidden = ['radius_secret'];

    protected static function newFactory(): IspServiceFactory
    {
        return IspServiceFactory::new();
    }

    protected function casts(): array
    {
        return [
            'technology' => BroadbandTechnology::class,
            'status' => IspServiceStatus::class,
            'radius_platform' => RadiusPlatform::class,
            'radius_secret' => 'encrypted',
            'download_limit_bps' => 'integer',
            'upload_limit_bps' => 'integer',
            'monthly_data_limit_bytes' => 'integer',
            'current_period_usage_bytes' => 'integer',
            'activated_at' => 'datetime',
            'suspended_at' => 'datetime',
            'radius_synced_at' => 'datetime',
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

    public function productService(): BelongsTo
    {
        return $this->belongsTo(Products_Service::class, 'product_service_id');
    }

    public function radiusSessions(): HasMany
    {
        return $this->hasMany(RadiusSession::class);
    }
}
