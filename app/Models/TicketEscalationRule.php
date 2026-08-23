<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int|null $team_id
 * @property int|null $department_id
 * @property int $minutes_without_response
 * @property string $action
 * @property int|null $target_user_id
 * @property bool $is_active
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read TicketDepartment|null $department
 * @property-read User|null $targetUser
 */
#[Fillable([
    'team_id',
    'department_id',
    'minutes_without_response',
    'action',
    'target_user_id',
    'is_active',
])]
class TicketEscalationRule extends Model
{
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'minutes_without_response' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    /**
     * @param  Builder<TicketEscalationRule>  $query
     * @return Builder<TicketEscalationRule>
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(TicketDepartment::class, 'department_id');
    }

    public function targetUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'target_user_id');
    }
}
