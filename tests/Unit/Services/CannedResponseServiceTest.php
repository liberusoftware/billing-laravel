<?php

namespace Tests\Unit\Services;

use App\Models\CannedResponse;
use App\Models\Team;
use App\Models\User;
use App\Services\CannedResponseService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CannedResponseServiceTest extends TestCase
{
    use RefreshDatabase;

    protected CannedResponseService $cannedResponseService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->cannedResponseService = new CannedResponseService;
    }

    public function test_can_get_canned_response_by_shortcode(): void
    {
        CannedResponse::create([
            'title' => 'Welcome Message',
            'shortcode' => 'welcome',
            'content' => 'Welcome {{client_name}}!',
            'is_active' => true,
        ]);

        $response = $this->cannedResponseService->getByShortcode('welcome');

        $this->assertNotNull($response);
        $this->assertEquals('Welcome Message', $response->title);
    }

    public function test_can_replace_variables_in_response(): void
    {
        $response = CannedResponse::create([
            'title' => 'Welcome Message',
            'shortcode' => 'welcome',
            'content' => 'Hello {{client_name}}, your ticket number is {{ticket_id}}.',
            'is_active' => true,
        ]);

        $content = $this->cannedResponseService->use($response, [
            'client_name' => 'John Doe',
            'ticket_id' => '12345',
        ]);

        $this->assertEquals('Hello John Doe, your ticket number is 12345.', $content);
    }

    public function test_usage_count_increments(): void
    {
        $response = CannedResponse::create([
            'title' => 'Test Response',
            'shortcode' => 'test',
            'content' => 'Test content',
            'is_active' => true,
            'usage_count' => 0,
        ]);

        $this->cannedResponseService->use($response);

        $this->assertEquals(1, $response->fresh()->usage_count);
        $this->assertNotNull($response->fresh()->last_used_at);
    }

    public function test_can_search_canned_responses(): void
    {
        CannedResponse::create([
            'title' => 'Password Reset',
            'shortcode' => 'pwd-reset',
            'content' => 'Instructions for resetting password',
            'is_active' => true,
        ]);

        CannedResponse::create([
            'title' => 'Account Setup',
            'shortcode' => 'account',
            'content' => 'Welcome to your account',
            'is_active' => true,
        ]);

        $results = $this->cannedResponseService->search('password');

        $this->assertCount(1, $results);
        $this->assertEquals('Password Reset', $results->first()->title);
    }

    public function test_can_get_most_used_responses(): void
    {
        CannedResponse::create([
            'title' => 'Popular Response',
            'shortcode' => 'popular',
            'content' => 'Popular content',
            'is_active' => true,
            'usage_count' => 100,
        ]);

        CannedResponse::create([
            'title' => 'Unpopular Response',
            'shortcode' => 'unpopular',
            'content' => 'Unpopular content',
            'is_active' => true,
            'usage_count' => 5,
        ]);

        $mostUsed = $this->cannedResponseService->getMostUsed(1);

        $this->assertCount(1, $mostUsed);
        $this->assertEquals('Popular Response', $mostUsed->first()->title);
    }

    public function test_available_variables(): void
    {
        $variables = CannedResponseService::getAvailableVariables();

        $this->assertIsArray($variables);
        $this->assertArrayHasKey('client_name', $variables);
        $this->assertArrayHasKey('ticket_id', $variables);
        $this->assertArrayHasKey('invoice_number', $variables);
    }

    public function test_unsupplied_variables_are_left_intact(): void
    {
        $response = CannedResponse::create([
            'title' => 'Partial', 'shortcode' => 'partial',
            'content' => 'Hi {{client_name}}, ref {{invoice_number}}.',
            'is_active' => true,
        ]);

        $content = $this->cannedResponseService->use($response, ['client_name' => 'Jane']);

        $this->assertEquals('Hi Jane, ref {{invoice_number}}.', $content);
    }

    public function test_get_all_includes_global_and_team_scoped_only(): void
    {
        $user = User::factory()->create();
        $mine = Team::factory()->create(['user_id' => $user->id]);
        $other = Team::factory()->create(['user_id' => $user->id]);
        $teamId = $mine->id;
        CannedResponse::create(['title' => 'Global', 'shortcode' => 'g', 'content' => 'x', 'is_active' => true]);
        CannedResponse::create(['title' => 'Mine', 'shortcode' => 'm', 'content' => 'x', 'team_id' => $teamId, 'is_active' => true]);
        CannedResponse::create(['title' => 'Theirs', 'shortcode' => 't', 'content' => 'x', 'team_id' => $other->id, 'is_active' => true]);
        CannedResponse::create(['title' => 'Inactive', 'shortcode' => 'i', 'content' => 'x', 'team_id' => $teamId, 'is_active' => false]);

        $results = $this->cannedResponseService->getAll($teamId);

        $this->assertEqualsCanonicalizing(['Global', 'Mine'], $results->pluck('title')->all());
    }

    public function test_get_all_filters_by_category(): void
    {
        CannedResponse::create(['title' => 'Billing one', 'shortcode' => 'b1', 'content' => 'x', 'category' => 'billing', 'is_active' => true]);
        CannedResponse::create(['title' => 'Support one', 'shortcode' => 's1', 'content' => 'x', 'category' => 'support', 'is_active' => true]);

        $results = $this->cannedResponseService->getAll(null, 'billing');

        $this->assertCount(1, $results);
        $this->assertEquals('Billing one', $results->first()->title);
    }

    public function test_get_categories_returns_distinct_non_null(): void
    {
        CannedResponse::create(['title' => 'a', 'shortcode' => 'a', 'content' => 'x', 'category' => 'billing', 'is_active' => true]);
        CannedResponse::create(['title' => 'b', 'shortcode' => 'b', 'content' => 'x', 'category' => 'billing', 'is_active' => true]);
        CannedResponse::create(['title' => 'c', 'shortcode' => 'c', 'content' => 'x', 'category' => null, 'is_active' => true]);

        $categories = $this->cannedResponseService->getCategories();

        $this->assertEquals(['billing'], array_values($categories));
    }
}
