<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\TaskPriority;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $project_id
 * @property string $title
 * @property string|null $description
 * @property bool $is_complete
 * @property Carbon|null $completed_at
 * @property Carbon|null $due_date
 * @property TaskPriority $priority
 * @property int|null $assigned_to
 * @property int $sort_order
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Project $project
 * @property-read User|null $assignee
 */
#[Fillable([
    'project_id',
    'title',
    'description',
    'is_complete',
    'completed_at',
    'due_date',
    'priority',
    'assigned_to',
    'sort_order',
])]
class Task extends Model
{
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_complete' => 'boolean',
            'completed_at' => 'datetime',
            'due_date' => 'date',
            'priority' => TaskPriority::class,
        ];
    }

    public function markComplete(): void
    {
        $this->update([
            'is_complete' => true,
            'completed_at' => now(),
        ]);
    }

    /**
     * @param  Builder<Task>  $query
     * @return Builder<Task>
     */
    public function scopeOutstanding(Builder $query): Builder
    {
        return $query->where('is_complete', false);
    }

    /**
     * @param  Builder<Task>  $query
     * @return Builder<Task>
     */
    public function scopeCompleted(Builder $query): Builder
    {
        return $query->where('is_complete', true);
    }

    /**
     * Outstanding tasks due on or before $days from now (priority by urgency).
     *
     * @param  Builder<Task>  $query
     * @return Builder<Task>
     */
    public function scopeDueWithin(Builder $query, int $days): Builder
    {
        return $query
            ->where('is_complete', false)
            ->whereNotNull('due_date')
            ->whereDate('due_date', '<=', now()->addDays($days));
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    /**
     * @return HasMany<TimeEntry, $this>
     */
    public function timeEntries(): HasMany
    {
        return $this->hasMany(TimeEntry::class);
    }
}
