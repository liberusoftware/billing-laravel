<?php

declare(strict_types=1);

namespace App\Models;

use App\Traits\HasTeam;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'team_id', 'infrastructure_asset_id', 'name', 'cidr', 'address_family',
    'first_address', 'last_address', 'next_address', 'gateway', 'vlan_id',
])]
class IpPool extends Model
{
    use HasTeam;

    protected function casts(): array
    {
        return ['address_family' => 'integer', 'vlan_id' => 'integer'];
    }

    public function infrastructureAsset(): BelongsTo
    {
        return $this->belongsTo(InfrastructureAsset::class);
    }

    /** @return HasMany<IpAddress, $this> */
    public function addresses(): HasMany
    {
        return $this->hasMany(IpAddress::class);
    }
}
