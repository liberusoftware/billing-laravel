<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\InfrastructureAssetType;
use App\Traits\HasTeam;
use Database\Factories\InfrastructureAssetFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'team_id', 'parent_id', 'asset_type', 'name', 'hostname', 'serial_number',
    'vendor', 'model', 'location', 'status', 'metadata',
])]
class InfrastructureAsset extends Model
{
    use HasFactory;
    use HasTeam;

    protected static function newFactory(): InfrastructureAssetFactory
    {
        return InfrastructureAssetFactory::new();
    }

    protected function casts(): array
    {
        return ['asset_type' => InfrastructureAssetType::class, 'metadata' => 'array'];
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id');
    }

    public function ipPools(): HasMany
    {
        return $this->hasMany(IpPool::class);
    }
}
