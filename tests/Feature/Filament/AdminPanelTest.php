<?php

declare(strict_types=1);

namespace Tests\Feature\Filament;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AdminPanelTest extends TestCase
{
    use RefreshDatabase;

    public function test_unauthenticated_user_is_redirected_from_admin(): void
    {
        $response = $this->get('/admin');

        $response->assertRedirect();
    }

    public function test_admin_login_page_is_accessible(): void
    {
        $response = $this->get('/admin/login');

        $response->assertStatus(200);
    }

    public function test_authenticated_user_can_access_admin_panel(): void
    {
        Role::findOrCreate('admin', 'web');
        $user = User::factory()->withPersonalTeam()->create();
        $user->assignRole('admin');

        $response = $this->actingAs($user)->get('/admin');

        // Should either render the dashboard (200) or redirect within the panel (302)
        $this->assertContains($response->getStatusCode(), [200, 302]);
    }

    public function test_user_without_admin_role_is_forbidden_from_admin(): void
    {
        $user = User::factory()->withPersonalTeam()->create();

        $response = $this->actingAs($user)->get('/admin');

        // No admin role -> Filament denies access (403) or redirects away.
        $this->assertContains($response->getStatusCode(), [403, 302]);
    }

    public function test_admin_panel_renders_login_form(): void
    {
        $response = $this->get('/admin/login');

        $response->assertStatus(200)->assertSeeHtml('email')->assertSeeHtml('password');
    }
}
