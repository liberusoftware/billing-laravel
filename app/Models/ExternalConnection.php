<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\ConnectorType;
use App\Traits\HasTeam;
use Database\Factories\ExternalConnectionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $team_id
 * @property ConnectorType $connector_type
 * @property string $provider
 * @property string $name
 * @property string $base_url
 * @property string $access_token
 * @property string|null $signing_secret
 * @property array<string, string>|null $resource_mappings
 * @property list<string>|null $event_subscriptions
 * @property bool $is_active
 * @property Carbon|null $last_synced_at
 */
#[Fillable([
    'team_id', 'connector_type', 'provider', 'name', 'base_url', 'access_token',
    'signing_secret', 'resource_mappings', 'event_subscriptions', 'is_active', 'last_synced_at',
])]
class ExternalConnection extends Model
{
    use HasFactory;
    use HasTeam;

    protected $hidden = ['access_token', 'signing_secret'];

    protected static function newFactory(): ExternalConnectionFactory
    {
        return ExternalConnectionFactory::new();
    }

    protected function casts(): array
    {
        return [
            'connector_type' => ConnectorType::class,
            'access_token' => 'encrypted',
            'signing_secret' => 'encrypted',
            'resource_mappings' => 'array',
            'event_subscriptions' => 'array',
            'is_active' => 'boolean',
            'last_synced_at' => 'datetime',
        ];
    }

    /** @return HasMany<ExternalSyncRecord, $this> */
    public function syncRecords(): HasMany
    {
        return $this->hasMany(ExternalSyncRecord::class);
    }

    /** @return HasMany<DomainEventDelivery, $this> */
    public function eventDeliveries(): HasMany
    {
        return $this->hasMany(DomainEventDelivery::class);
    }
}
