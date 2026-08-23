<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['team_id', 'name', 'slug', 'is_primary', 'theme', 'email_branding'])]
class OrganisationBrand extends Model
{
    protected static function booted(): void
    {
        static::saving(function (self $brand): void {
            if ($brand->is_primary && $brand->isDirty('is_primary')) {
                static::query()
                    ->where('team_id', $brand->team_id)
                    ->when($brand->exists, fn ($query) => $query->whereKeyNot($brand->getKey()))
                    ->update(['is_primary' => false]);
            }
        });
    }

    protected function casts(): array
    {
        return ['is_primary' => 'boolean', 'theme' => 'array', 'email_branding' => 'array'];
    }

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    public function domains(): HasMany
    {
        return $this->hasMany(BrandDomain::class);
    }
}
