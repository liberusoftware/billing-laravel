<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use Override;

/**
 * @property int $id
 * @property int $user_id
 * @property string $provider
 * @property string $token
 * @property string|null $refresh_token
 * @property Carbon|null $expires_at
 * @property array|null $scopes
 * @property array|null $settings
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read User|null $user
 */
#[Fillable([
    'user_id',
    'provider',
    'token',
    'refresh_token',
    'expires_at',
    'scopes',
    'settings',
])]
#[Hidden([
    'token',
    'refresh_token',
])]
class Integration extends Model
{
    #[Override]
    protected function casts(): array
    {

        return [
            'expires_at' => 'datetime',
            'settings' => 'array',
            'scopes' => 'array',
        ];

    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
