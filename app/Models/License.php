<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\LicenseStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int|null $team_id
 * @property int $customer_id
 * @property int|null $product_service_id
 * @property string $license_key
 * @property LicenseStatus $status
 * @property int $max_instances
 * @property Carbon|null $valid_until
 * @property string|null $notes
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Team|null $team
 * @property-read Customer $customer
 * @property-read Products_Service|null $productService
 * @property-read Collection<int, LicenseInstance> $instances
 */
#[Fillable([
    'team_id',
    'customer_id',
    'product_service_id',
    'license_key',
    'status',
    'max_instances',
    'valid_until',
    'notes',
])]
class License extends Model
{
    use HasFactory;
    use SoftDeletes;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => LicenseStatus::class,
            'valid_until' => 'date',
        ];
    }

    /**
     * @param  Builder<License>  $query
     * @return Builder<License>
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', LicenseStatus::Active->value);
    }

    public function isUsable(): bool
    {
        return $this->status === LicenseStatus::Active
            && ($this->valid_until === null || $this->valid_until->isFuture());
    }

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function productService(): BelongsTo
    {
        return $this->belongsTo(Products_Service::class, 'product_service_id');
    }

    /**
     * @return HasMany<LicenseInstance, $this>
     */
    public function instances(): HasMany
    {
        return $this->hasMany(LicenseInstance::class);
    }

    /**
     * @return HasMany<LicenseReissue, $this>
     */
    public function reissues(): HasMany
    {
        return $this->hasMany(LicenseReissue::class);
    }
}
