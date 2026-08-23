<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\KnowledgeBaseArticle;
use App\Services\KnowledgeBaseService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class KnowledgeBaseController extends Controller
{
    public function __construct(
        protected KnowledgeBaseService $knowledgeBaseService
    ) {}

    /**
     * Get all categories
     */
    public function categories(): JsonResponse
    {
        return response()->json(
            [
                'data' => $this->knowledgeBaseService->getCategoryTree(),
            ]
        );
    }

    /**
     * Search articles
     */
    public function search(Request $request): JsonResponse
    {
        $request->validate(
            [
                'q' => 'required|string|min:2',
                'category_id' => 'nullable|exists:knowledge_base_categories,id',
                'limit' => 'nullable|integer|min:1|max:100',
            ]
        );

        $articles = $this->knowledgeBaseService->search(
            $request->q,
            $request->category_id,
            $request->limit ?? 20
        );

        return response()->json(
            [
                'data' => $articles,
            ]
        );
    }

    /**
     * Get featured articles
     */
    public function featured(Request $request): JsonResponse
    {
        $limit = $request->input(
            'limit',
            5
        );
        $articles = $this->knowledgeBaseService->getFeatured($limit);

        return response()->json(
            [
                'data' => $articles,
            ]
        );
    }

    /**
     * Get popular articles
     */
    public function popular(Request $request): JsonResponse
    {
        $limit = $request->input(
            'limit',
            10
        );
        $articles = $this->knowledgeBaseService->getPopular($limit);

        return response()->json(
            [
                'data' => $articles,
            ]
        );
    }

    /**
     * Get article by slug
     */
    public function show(string $slug): JsonResponse
    {
        $article = KnowledgeBaseArticle::where(
            'slug',
            $slug
        )
            ->where(
                'is_published',
                true
            )
            ->with(
                [
                    'category',
                    'author',
                ]
            )
            ->firstOrFail();

        // Track view
        $this->knowledgeBaseService->trackView($article);

        // Get related articles
        $related = $this->knowledgeBaseService->getRelated($article);

        return response()->json(
            [
                'data' => $article,
                'related' => $related,
            ]
        );
    }

    /**
     * Mark article as helpful
     */
    public function markHelpful(string $slug): JsonResponse
    {
        $article = KnowledgeBaseArticle::where(
            'slug',
            $slug
        )
            ->where(
                'is_published',
                true
            )
            ->firstOrFail();

        $this->knowledgeBaseService->markHelpful($article);

        return response()->json(
            [
                'message' => 'Thank you for your feedback',
            ]
        );
    }

    /**
     * Mark article as not helpful
     */
    public function markNotHelpful(string $slug): JsonResponse
    {
        $article = KnowledgeBaseArticle::where(
            'slug',
            $slug
        )
            ->where(
                'is_published',
                true
            )
            ->firstOrFail();

        $this->knowledgeBaseService->markNotHelpful($article);

        return response()->json(
            [
                'message' => 'Thank you for your feedback',
            ]
        );
    }

    /**
     * Get articles by category
     */
    public function byCategory(int $categoryId): JsonResponse
    {
        $articles = $this->knowledgeBaseService->getByCategory($categoryId);

        return response()->json(
            [
                'data' => $articles,
            ]
        );
    }
}
