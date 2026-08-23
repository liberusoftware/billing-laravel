<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Override;

/**
 * @property int $id
 * @property int $category_id
 * @property int $author_id
 * @property string $title
 * @property string $slug
 * @property string|null $summary
 * @property string $content
 * @property int $sort_order
 * @property bool $is_published
 * @property bool $is_featured
 * @property int $view_count
 * @property int $helpful_count
 * @property int $not_helpful_count
 * @property Carbon|null $published_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read KnowledgeBaseCategory|null $category
 * @property-read User|null $author
 */
#[Fillable([
    'category_id',
    'author_id',
    'title',
    'slug',
    'summary',
    'content',
    'sort_order',
    'is_published',
    'is_featured',
    'view_count',
    'helpful_count',
    'not_helpful_count',
    'published_at',
])]
class KnowledgeBaseArticle extends Model
{
    #[Override]
    protected function casts(): array
    {

        return [
            'is_published' => 'boolean',
            'is_featured' => 'boolean',
            'published_at' => 'datetime',
        ];

    }

    #[Override]
    protected static function boot(): void
    {
        parent::boot();

        static::creating(
            static function ($article): void {
                if (empty($article->slug)) {
                    $article->slug = Str::slug($article->title);
                }
            }
        );

        static::updating(
            static function ($article): void {
                if ($article->isDirty('is_published') && $article->is_published && ! $article->published_at) {
                    $article->published_at = now();
                }
            }
        );
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(KnowledgeBaseCategory::class);
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'author_id'
        );
    }

    public function incrementViewCount(): void
    {
        $this->increment('view_count');
    }

    public function markAsHelpful(): void
    {
        $this->increment('helpful_count');
    }

    public function markAsNotHelpful(): void
    {
        $this->increment('not_helpful_count');
    }

    public function getHelpfulnessRatio(): float
    {
        $total = $this->helpful_count + $this->not_helpful_count;

        if ($total === 0) {
            return 0;
        }

        return round(
            ($this->helpful_count / $total) * 100,
            2
        );
    }
}
