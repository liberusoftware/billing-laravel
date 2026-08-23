<?php

declare(strict_types=1);

namespace App\Models;

use App\Traits\HasTeam;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use Override;

/**
 * @property int $id
 * @property int $team_id
 * @property string $name
 * @property string $type
 * @property Carbon|null $start_date
 * @property Carbon|null $end_date
 * @property array|null $filters
 * @property string|null $format
 * @property array|null $parameters
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Team|null $team
 */
#[Fillable([
    'name',
    'type',
    // revenue, expense, outstanding
    'start_date',
    'end_date',
    'filters',
    'format',
    'parameters',
    'schedule',
    'last_generated_at',
    'team_id',
])]
class Report extends Model
{
    use HasTeam;

    #[Override]
    protected function casts(): array
    {
        return [
            'start_date' => 'datetime',
            'end_date' => 'datetime',
            'filters' => 'array',
            'parameters' => 'array',
            'schedule' => 'array',
            'last_generated_at' => 'datetime',
        ];
    }

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }
}
