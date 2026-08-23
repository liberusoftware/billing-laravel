<?php

namespace App\Services;

use App\Models\KnowledgeBaseArticle;
use App\Models\KnowledgeBaseCategory;
use Illuminate\Database\Eloquent\Collection;

class KnowledgeBaseService
{
    /**
     * Search articles by query
     */
    public function search(string $query, ?int $categoryId = null, int $limit = 20): Collection
    {
        $queryBuilder = KnowledgeBaseArticle::query()
            ->where(
                'is_published',
                true
            )
            ->where(
                function ($q) use ($query): void {
                    if (config('database.default') !== 'sqlite') {
                        $q->whereFullText(
                            [
                                'title',
                                'content',
                            ],
                            $query
                        );
                    }
                    $q->orWhere(
                        'title',
                        'like',
                        "%{$query}%"
                    )
                        ->orWhere(
                            'content',
                            'like',
                            "%{$query}%"
                        );
                }
            );

        if ($categoryId) {
            $queryBuilder->where(
                'category_id',
                $categoryId
            );
        }

        return $queryBuilder
            ->orderByDesc('is_featured')
            ->orderByDesc('helpful_count')
            ->limit($limit)
            ->get();
    }

    /**
     * Get featured articles
     */
    public function getFeatured(int $limit = 5): Collection
    {
        return KnowledgeBaseArticle::where(
            'is_published',
            true
        )
            ->where(
                'is_featured',
                true
            )
            ->orderBy('sort_order')
            ->limit($limit)
            ->get();
    }

    /**
     * Get popular articles
     */
    public function getPopular(int $limit = 10): Collection
    {
        return KnowledgeBaseArticle::where(
            'is_published',
            true
        )
            ->orderByDesc('view_count')
            ->limit($limit)
            ->get();
    }

    /**
     * Get articles by category
     */
    public function getByCategory(int $categoryId): Collection
    {
        return KnowledgeBaseArticle::where(
            'category_id',
            $categoryId
        )
            ->where(
                'is_published',
                true
            )
            ->orderBy('sort_order')
            ->get();
    }

    /**
     * Get all categories with article counts
     */
    public function getCategoriesWithCounts(): Collection
    {
        return KnowledgeBaseCategory::where(
            'is_active',
            true
        )
            ->withCount(['publishedArticles'])
            ->orderBy('sort_order')
            ->get();
    }

    /**
     * Get category tree
     */
    public function getCategoryTree(): Collection
    {
        return KnowledgeBaseCategory::where(
            'is_active',
            true
        )
            ->whereNull('parent_id')
            ->with(
                [
                    'children' => function ($query): void {
                        $query->where(
                            'is_active',
                            true
                        )
                            ->orderBy('sort_order');
                    },
                ]
            )
            ->orderBy('sort_order')
            ->get();
    }

    /**
     * Track article view
     */
    public function trackView(KnowledgeBaseArticle $article): void
    {
        $article->incrementViewCount();
    }

    /**
     * Mark article as helpful
     */
    public function markHelpful(KnowledgeBaseArticle $article): void
    {
        $article->markAsHelpful();
    }

    /**
     * Mark article as not helpful
     */
    public function markNotHelpful(KnowledgeBaseArticle $article): void
    {
        $article->markAsNotHelpful();
    }

    /**
     * Get related articles
     */
    public function getRelated(KnowledgeBaseArticle $article, int $limit = 5): Collection
    {
        return KnowledgeBaseArticle::where(
            'is_published',
            true
        )
            ->where(
                'id',
                '!=',
                $article->id
            )
            ->where(
                'category_id',
                $article->category_id
            )
            ->orderByDesc('helpful_count')
            ->limit($limit)
            ->get();
    }
}
