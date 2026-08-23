<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use Override;

/**
 * @property int $id
 * @property int|null $team_id
 * @property string $title
 * @property string $shortcode
 * @property string $content
 * @property string|null $category
 * @property bool $is_active
 * @property int $usage_count
 * @property Carbon|null $last_used_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Team|null $team
 */
#[Fillable([
    'team_id',
    'title',
    'shortcode',
    'content',
    'category',
    'is_active',
    'usage_count',
    'last_used_at',
])]
class CannedResponse extends Model
{
    #[Override]
    protected function casts(): array
    {

        return [
            'is_active' => 'boolean',
            'last_used_at' => 'datetime',
        ];

    }

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    public function use(): void
    {
        $this->increment('usage_count');
        $this->update(['last_used_at' => now()]);
    }

    public function replaceVariables(array $variables): string
    {
        $content = $this->content;

        foreach ($variables as $key => $value) {
            $content = str_replace(
                '{{'.$key.'}}',
                $value,
                $content
            );
        }

        return $content;
    }

    public static function getCategories(): array
    {
        return self::distinct('category')
            ->whereNotNull('category')
            ->pluck('category')
            ->toArray();
    }
}
