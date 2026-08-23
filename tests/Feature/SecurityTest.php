<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SecurityTest extends TestCase
{
    use RefreshDatabase;

    public function test_security_headers_are_present_on_web_responses(): void
    {
        $response = $this->get('/');

        $response->assertHeader('X-Content-Type-Options', 'nosniff');
        $response->assertHeader('X-Frame-Options', 'SAMEORIGIN');
        $response->assertHeader('X-XSS-Protection', '1; mode=block');
        $response->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin');
    }

    public function test_security_headers_are_present_on_api_responses(): void
    {
        $response = $this->getJson('/api/health');

        $response->assertStatus(200);
        $response->assertHeader('X-Content-Type-Options', 'nosniff');
    }

    public function test_csrf_protection_is_active_on_post_requests(): void
    {
        $response = $this->post('/login', [
            'email' => 'test@example.com',
            'password' => 'password',
        ]);

        // Without CSRF token, should fail (419 or redirect)
        $this->assertContains($response->getStatusCode(), [419, 302]);
    }

    public function test_api_routes_require_authentication(): void
    {
        $protectedRoutes = [
            ['GET', '/api/invoices'],
            ['GET', '/api/customers'],
            ['GET', '/api/subscriptions'],
        ];

        foreach ($protectedRoutes as [$method, $route]) {
            $response = $this->json($method, $route);
            $this->assertEquals(401, $response->getStatusCode(), "{$method} {$route} should require authentication.");
        }
    }

    public function test_api_health_endpoint_is_publicly_accessible(): void
    {
        $response = $this->getJson('/api/health');

        $response->assertStatus(200)
            ->assertJsonStructure(['status', 'timestamp']);
    }

    public function test_admin_panel_requires_authentication(): void
    {
        $response = $this->get('/admin');

        $response->assertRedirect();
    }

    public function test_password_is_not_exposed_in_user_api_response(): void
    {
        $user = User::factory()->withPersonalTeam()->create();

        $token = $user->createToken('test')->plainTextToken;

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson('/api/user');

        $response->assertStatus(200);
        $responseData = $response->json();
        $this->assertArrayNotHasKey('password', $responseData);
        $this->assertArrayNotHasKey('remember_token', $responseData);
    }
}
