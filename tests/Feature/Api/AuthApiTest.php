<?php

declare(strict_types=1);

namespace Tests\Feature\Api;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_obtain_api_token(): void
    {
        $user = User::factory()->withPersonalTeam()->create([
            'password' => bcrypt('password'),
        ]);

        $response = $this->postJson('/api/auth/token', [
            'email' => $user->email,
            'password' => 'password',
            'device_name' => 'test-device',
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure(['token']);
    }

    public function test_invalid_credentials_return_422(): void
    {
        $user = User::factory()->withPersonalTeam()->create([
            'password' => bcrypt('password'),
        ]);

        $response = $this->postJson('/api/auth/token', [
            'email' => $user->email,
            'password' => 'wrong-password',
            'device_name' => 'test-device',
        ]);

        $response->assertStatus(422);
    }

    public function test_missing_credentials_return_422(): void
    {
        $response = $this->postJson('/api/auth/token', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email', 'password', 'device_name']);
    }

    public function test_auth_token_endpoint_is_rate_limited(): void
    {
        $user = User::factory()->withPersonalTeam()->create([
            'password' => bcrypt('password'),
        ]);

        // Make 6 requests (limit is 5 per minute)
        for ($i = 0; $i < 5; $i++) {
            $this->postJson('/api/auth/token', [
                'email' => $user->email,
                'password' => 'wrong-password',
                'device_name' => 'test',
            ]);
        }

        $response = $this->postJson('/api/auth/token', [
            'email' => $user->email,
            'password' => 'wrong-password',
            'device_name' => 'test',
        ]);

        $response->assertStatus(429);
    }
}
