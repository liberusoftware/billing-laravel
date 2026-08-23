<?php

declare(strict_types=1);

namespace App\Models;

use App\Traits\HasTeam;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Override;

/**
 * @property int $id
 * @property int $team_id
 * @property int $days_before_reminder
 * @property int $reminder_frequency
 * @property int $max_reminders
 * @property bool $is_active
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Team|null $team
 */
#[Fillable([
    'team_id',
    'days_before_reminder',
    'reminder_frequency',
    'max_reminders',
    'is_active',
])]
class ReminderSetting extends Model
{
    use HasTeam;

    #[Override]
    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }
}
