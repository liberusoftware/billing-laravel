<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $license_id
 * @property int|null $reissued_by
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read License $license
 */
#[Fillable([
    'license_id',
    'reissued_by',
])]
class LicenseReissue extends Model
{
    public function license(): BelongsTo
    {
        return $this->belongsTo(License::class);
    }
}
