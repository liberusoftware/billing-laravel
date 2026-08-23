<?php

namespace Tests\Unit\Services;

use App\Models\KnowledgeBaseArticle;
use App\Models\KnowledgeBaseCategory;
use App\Models\User;
use App\Services\KnowledgeBaseService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class KnowledgeBaseServiceTest extends TestCase
{
    use RefreshDatabase;

    protected KnowledgeBaseService $kbService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->kbService = new KnowledgeBaseService;
    }

    public function test_can_search_articles(): void
    {
        $user = User::factory()->create();
        $category = KnowledgeBaseCategory::create([
            'name' => 'Test Category',
            'slug' => 'test-category',
            'is_active' => true,
        ]);

        KnowledgeBaseArticle::create([
            'category_id' => $category->id,
            'author_id' => $user->id,
            'title' => 'How to reset password',
            'slug' => 'how-to-reset-password',
            'content' => 'This is a guide on resetting your password',
            'is_published' => true,
        ]);

        $results = $this->kbService->search('password');

        $this->assertCount(1, $results);
        $this->assertEquals('How to reset password', $results->first()->title);
    }

    public function test_can_get_featured_articles(): void
    {
        $user = User::factory()->create();
        $category = KnowledgeBaseCategory::create([
            'name' => 'Test Category',
            'slug' => 'test-category',
            'is_active' => true,
        ]);

        KnowledgeBaseArticle::create([
            'category_id' => $category->id,
            'author_id' => $user->id,
            'title' => 'Featured Article',
            'slug' => 'featured-article',
            'content' => 'This is featured',
            'is_published' => true,
            'is_featured' => true,
        ]);

        $featured = $this->kbService->getFeatured();

        $this->assertCount(1, $featured);
        $this->assertTrue($featured->first()->is_featured);
    }

    public function test_can_track_article_views(): void
    {
        $user = User::factory()->create();
        $category = KnowledgeBaseCategory::create([
            'name' => 'Test Category',
            'slug' => 'test-category',
            'is_active' => true,
        ]);

        $article = KnowledgeBaseArticle::create([
            'category_id' => $category->id,
            'author_id' => $user->id,
            'title' => 'Test Article',
            'slug' => 'test-article',
            'content' => 'Test content',
            'is_published' => true,
            'view_count' => 0,
        ]);

        $this->kbService->trackView($article);

        $this->assertEquals(1, $article->fresh()->view_count);
    }

    public function test_can_mark_article_as_helpful(): void
    {
        $user = User::factory()->create();
        $category = KnowledgeBaseCategory::create([
            'name' => 'Test Category',
            'slug' => 'test-category',
            'is_active' => true,
        ]);

        $article = KnowledgeBaseArticle::create([
            'category_id' => $category->id,
            'author_id' => $user->id,
            'title' => 'Test Article',
            'slug' => 'test-article',
            'content' => 'Test content',
            'is_published' => true,
            'helpful_count' => 0,
        ]);

        $this->kbService->markHelpful($article);

        $this->assertEquals(1, $article->fresh()->helpful_count);
    }

    public function test_can_get_category_tree(): void
    {
        $parent = KnowledgeBaseCategory::create([
            'name' => 'Parent Category',
            'slug' => 'parent-category',
            'is_active' => true,
        ]);

        $child = KnowledgeBaseCategory::create([
            'name' => 'Child Category',
            'slug' => 'child-category',
            'parent_id' => $parent->id,
            'is_active' => true,
        ]);

        $tree = $this->kbService->getCategoryTree();

        $this->assertCount(1, $tree);
        $this->assertEquals('Parent Category', $tree->first()->name);
        $this->assertCount(1, $tree->first()->children);
        // child resolves back to its parent
        $this->assertEquals($parent->id, $child->fresh()->parent->id);
    }

    public function test_search_matches_content_and_filters_by_category(): void
    {
        $user = User::factory()->create();
        $catA = KnowledgeBaseCategory::create(['name' => 'A', 'slug' => 'a', 'is_active' => true]);
        $catB = KnowledgeBaseCategory::create(['name' => 'B', 'slug' => 'b', 'is_active' => true]);

        // matches on content only (not title)
        KnowledgeBaseArticle::create([
            'category_id' => $catA->id, 'author_id' => $user->id,
            'title' => 'Billing overview', 'slug' => 'billing-a',
            'content' => 'How to configure SMTP relay settings', 'is_published' => true,
        ]);
        KnowledgeBaseArticle::create([
            'category_id' => $catB->id, 'author_id' => $user->id,
            'title' => 'SMTP guide', 'slug' => 'smtp-b',
            'content' => 'Unrelated body', 'is_published' => true,
        ]);
        // unpublished must be excluded
        KnowledgeBaseArticle::create([
            'category_id' => $catA->id, 'author_id' => $user->id,
            'title' => 'Draft SMTP', 'slug' => 'draft', 'content' => 'SMTP',
            'is_published' => false,
        ]);

        $all = $this->kbService->search('SMTP');
        $this->assertCount(2, $all);

        $scoped = $this->kbService->search('SMTP', $catA->id);
        $this->assertCount(1, $scoped);
        $this->assertEquals('Billing overview', $scoped->first()->title);
    }

    public function test_can_mark_article_as_not_helpful_and_compute_ratio(): void
    {
        $user = User::factory()->create();
        $category = KnowledgeBaseCategory::create(['name' => 'C', 'slug' => 'c', 'is_active' => true]);

        $article = KnowledgeBaseArticle::create([
            'category_id' => $category->id, 'author_id' => $user->id,
            'title' => 'Ratio', 'slug' => 'ratio', 'content' => 'x',
            'is_published' => true, 'helpful_count' => 3, 'not_helpful_count' => 0,
        ]);

        $this->kbService->markNotHelpful($article);

        $this->assertEquals(1, $article->fresh()->not_helpful_count);
        $this->assertEquals(75.0, $article->fresh()->getHelpfulnessRatio());
    }

    public function test_can_get_related_articles_in_same_category_excluding_self(): void
    {
        $user = User::factory()->create();
        $category = KnowledgeBaseCategory::create(['name' => 'C', 'slug' => 'c', 'is_active' => true]);
        $other = KnowledgeBaseCategory::create(['name' => 'O', 'slug' => 'o', 'is_active' => true]);

        $article = KnowledgeBaseArticle::create([
            'category_id' => $category->id, 'author_id' => $user->id,
            'title' => 'Main', 'slug' => 'main', 'content' => 'x', 'is_published' => true,
        ]);
        KnowledgeBaseArticle::create([
            'category_id' => $category->id, 'author_id' => $user->id,
            'title' => 'Sibling', 'slug' => 'sibling', 'content' => 'x',
            'is_published' => true, 'helpful_count' => 10,
        ]);
        // different category - must not appear
        KnowledgeBaseArticle::create([
            'category_id' => $other->id, 'author_id' => $user->id,
            'title' => 'Foreign', 'slug' => 'foreign', 'content' => 'x', 'is_published' => true,
        ]);

        $related = $this->kbService->getRelated($article);

        $this->assertCount(1, $related);
        $this->assertEquals('Sibling', $related->first()->title);
    }

    public function test_popular_and_by_category_respect_publish_and_order(): void
    {
        $user = User::factory()->create();
        $category = KnowledgeBaseCategory::create(['name' => 'C', 'slug' => 'c', 'is_active' => true]);

        KnowledgeBaseArticle::create([
            'category_id' => $category->id, 'author_id' => $user->id,
            'title' => 'Low', 'slug' => 'low', 'content' => 'x',
            'is_published' => true, 'view_count' => 1, 'sort_order' => 2,
        ]);
        KnowledgeBaseArticle::create([
            'category_id' => $category->id, 'author_id' => $user->id,
            'title' => 'High', 'slug' => 'high', 'content' => 'x',
            'is_published' => true, 'view_count' => 99, 'sort_order' => 1,
        ]);
        KnowledgeBaseArticle::create([
            'category_id' => $category->id, 'author_id' => $user->id,
            'title' => 'Hidden', 'slug' => 'hidden', 'content' => 'x',
            'is_published' => false, 'view_count' => 500,
        ]);

        $popular = $this->kbService->getPopular();
        $this->assertCount(2, $popular);
        $this->assertEquals('High', $popular->first()->title);

        $byCategory = $this->kbService->getByCategory($category->id);
        $this->assertCount(2, $byCategory);
        $this->assertEquals('High', $byCategory->first()->title); // sort_order 1 first
    }

    public function test_categories_with_counts_only_counts_published(): void
    {
        $user = User::factory()->create();
        $category = KnowledgeBaseCategory::create(['name' => 'C', 'slug' => 'c', 'is_active' => true]);
        KnowledgeBaseArticle::create([
            'category_id' => $category->id, 'author_id' => $user->id,
            'title' => 'Pub', 'slug' => 'pub', 'content' => 'x', 'is_published' => true,
        ]);
        KnowledgeBaseArticle::create([
            'category_id' => $category->id, 'author_id' => $user->id,
            'title' => 'Unpub', 'slug' => 'unpub', 'content' => 'x', 'is_published' => false,
        ]);

        $cats = $this->kbService->getCategoriesWithCounts();
        $this->assertEquals(1, $cats->first()->published_articles_count);
    }
}
