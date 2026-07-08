<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $license_id
 * @property string $identifier
 * @property string|null $ip_address
 * @property Carbon|null $last_validated_at
 * @property string|null $local_key
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read License $license
 */
#[Fillable([
    'license_id',
    'identifier',
    'ip_address',
    'last_validated_at',
    'local_key',
])]
class LicenseInstance extends Model
{
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'last_validated_at' => 'datetime',
        ];
    }

    public function license(): BelongsTo
    {
        return $this->belongsTo(License::class);
    }
}
