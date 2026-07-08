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
 * @property int $project_id
 * @property int|null $uploaded_by
 * @property string $path
 * @property string $original_name
 * @property string $mime
 * @property int $size
 * @property bool $customer_visible
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Project $project
 * @property-read User|null $uploader
 */
#[Fillable([
    'project_id',
    'uploaded_by',
    'path',
    'original_name',
    'mime',
    'size',
    'customer_visible',
])]
class ProjectFile extends Model
{
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'size' => 'integer',
            'customer_visible' => 'boolean',
        ];
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    /**
     * @param  Builder<ProjectFile>  $query
     */
    public function scopeCustomerVisible(Builder $query): void
    {
        $query->where('customer_visible', true);
    }
}
