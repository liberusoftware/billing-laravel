<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Laravel\Jetstream\Jetstream;
use Laravel\Jetstream\TeamInvitation as JetstreamTeamInvitation;
use Override;

/**
 * @property int $id
 * @property int $team_id
 * @property string $email
 * @property string|null $role
 * @property string $token
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Team|null $team
 */
#[Fillable([
    'email',
    'role',
])]
class TeamInvitation extends JetstreamTeamInvitation
{
    /**
     * Bootstrap the model and its traits.
     */
    #[Override]
    protected static function boot(): void
    {
        parent::boot();

        static::creating(
            static function ($invitation): void {
                if (empty($invitation->token)) {
                    $invitation->token = Str::random(64);
                }
            }
        );
    }

    /**
     * Get the team that the invitation belongs to.
     */
    #[Override]
    public function team(): BelongsTo
    {
        return $this->belongsTo(Jetstream::teamModel());
    }
}
