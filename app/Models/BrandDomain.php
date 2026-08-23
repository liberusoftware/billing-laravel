<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['organisation_brand_id', 'domain', 'is_primary', 'is_verified', 'verified_at'])]
class BrandDomain extends Model
{
    protected static function booted(): void
    {
        static::saving(function (self $domain): void {
            $domain->domain = strtolower(rtrim($domain->domain, '.'));

            if ($domain->is_primary && $domain->isDirty('is_primary')) {
                static::query()
                    ->where('organisation_brand_id', $domain->organisation_brand_id)
                    ->when($domain->exists, fn ($query) => $query->whereKeyNot($domain->getKey()))
                    ->update(['is_primary' => false]);
            }
        });
    }

    protected function casts(): array
    {
        return ['is_primary' => 'boolean', 'is_verified' => 'boolean', 'verified_at' => 'datetime'];
    }

    public function brand(): BelongsTo
    {
        return $this->belongsTo(OrganisationBrand::class, 'organisation_brand_id');
    }
}
