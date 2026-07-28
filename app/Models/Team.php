<?php

namespace App\Models;

use App\Enums\OrganisationType;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Carbon;
use Laravel\Jetstream\Events\TeamCreated;
use Laravel\Jetstream\Events\TeamDeleted;
use Laravel\Jetstream\Events\TeamUpdated;
use Laravel\Jetstream\Team as JetstreamTeam;
use Override;

/**
 * @property int $id
 * @property int $user_id
 * @property int|null $parent_team_id
 * @property string $name
 * @property OrganisationType $organisation_type
 * @property string|null $slug
 * @property string|null $custom_domain
 * @property string $database_mode
 * @property array<string, mixed>|null $branding
 * @property Carbon|null $archived_at
 */
#[Fillable([
    'name',
    'personal_team',
    'user_id',
    'is_default_for_registration',
    'parent_team_id',
    'organisation_type',
    'slug',
    'custom_domain',
    'database_mode',
    'branding',
    'archived_at',
])]
class Team extends JetstreamTeam
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    #[Override]
    protected $fillable = [
        'name',
        'personal_team',
        'user_id',
        'is_default_for_registration',
        'parent_team_id',
        'organisation_type',
        'slug',
        'custom_domain',
        'database_mode',
        'branding',
        'archived_at',
    ];

    /**
     * The event map for the model.
     *
     * @var array<string, class-string>
     */
    #[Override]
    protected $dispatchesEvents = [
        'created' => TeamCreated::class,
        'updated' => TeamUpdated::class,
        'deleted' => TeamDeleted::class,
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    #[Override]
    protected function casts(): array
    {
        return [
            'personal_team' => 'boolean',
            'is_default_for_registration' => 'boolean',
            'organisation_type' => OrganisationType::class,
            'branding' => 'array',
            'archived_at' => 'datetime',
        ];
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_team_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_team_id');
    }

    public function resellerAgreements(): HasMany
    {
        return $this->hasMany(ResellerAgreement::class, 'provider_team_id');
    }

    public function resellerAgreement(): HasOne
    {
        return $this->hasOne(ResellerAgreement::class, 'reseller_team_id');
    }

    public function brands(): HasMany
    {
        return $this->hasMany(OrganisationBrand::class);
    }

    /**
     * Enforce a single default-for-registration team: turning the flag on for one
     * team clears it on every other. Done via the query builder (not model events)
     * so it does not recurse through this saving hook.
     */
    protected static function booted(): void
    {
        static::saving(function (Team $team): void {
            if ($team->is_default_for_registration && $team->isDirty('is_default_for_registration')) {
                static::query()
                    ->where('is_default_for_registration', true)
                    ->when($team->exists, fn ($query) => $query->whereKeyNot($team->getKey()))
                    ->update(['is_default_for_registration' => false]);
            }
        });
    }

    /**
     * The team new registrants are attached to, if an admin has designated one.
     */
    public static function defaultForRegistration(): ?self
    {
        return static::query()->where('is_default_for_registration', true)->first();
    }
}
