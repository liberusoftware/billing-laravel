<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $user_id
 * @property int|null $subscription_id
 * @property string|null $domain_name
 * @property int|null $project_id
 * @property int|null $department_id
 * @property int|null $assigned_to
 * @property string $title
 * @property string $description
 * @property string $status
 * @property string $priority
 * @property Carbon|null $sla_breached_at
 * @property array|null $custom_fields
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read User|null $user
 * @property-read Project|null $project
 * @property-read TicketDepartment|null $department
 * @property-read User|null $assignee
 * @property-read Collection<int, TicketResponse> $responses
 */
#[Fillable([
    'user_id',
    'subscription_id',
    'domain_name',
    'project_id',
    'department_id',
    'assigned_to',
    'title',
    'description',
    'status',
    'priority',
    'sla_breached_at',
    'custom_fields',
])]
class Ticket extends Model
{
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'custom_fields' => 'array',
            'sla_breached_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class, 'project_id');
    }

    public function subscription(): BelongsTo
    {
        return $this->belongsTo(Subscription::class);
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(TicketDepartment::class);
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    /**
     * @param  Builder<Ticket>  $query
     * @return Builder<Ticket>
     */
    public function scopeAssignedTo(Builder $query, int $userId): Builder
    {
        return $query->where('assigned_to', $userId);
    }

    public function responses(): HasMany
    {
        return $this->hasMany(TicketResponse::class);
    }
}
