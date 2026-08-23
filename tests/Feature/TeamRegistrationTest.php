<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Team;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Fortify\Features;
use Laravel\Jetstream\Jetstream;
use Tests\TestCase;

class TeamRegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_only_one_team_can_be_default_for_registration(): void
    {
        $owner = User::factory()->create();
        $first = Team::factory()->create(['user_id' => $owner->id, 'is_default_for_registration' => true]);
        $second = Team::factory()->create(['user_id' => $owner->id, 'is_default_for_registration' => true]);

        $this->assertFalse($first->fresh()->is_default_for_registration, 'Setting a new default must clear the previous one.');
        $this->assertTrue($second->fresh()->is_default_for_registration);
        $this->assertTrue(Team::defaultForRegistration()->is($second));
    }

    public function test_registration_attaches_user_to_flagged_default_team_plus_personal_team(): void
    {
        if (! Features::enabled(Features::registration())) {
            $this->markTestSkipped('Registration support is not enabled.');
        }

        $owner = User::factory()->create();
        $default = Team::factory()->create([
            'user_id' => $owner->id,
            'name' => 'Acme Default',
            'personal_team' => false,
            'is_default_for_registration' => true,
        ]);

        $this->post('/register', [
            'name' => 'Reg User',
            'email' => 'reg@example.com',
            'password' => 'New-Passw0rd!23',
            'password_confirmation' => 'New-Passw0rd!23',
            'terms' => Jetstream::hasTermsAndPrivacyPolicyFeature(),
        ])->assertRedirect();

        $user = User::where('email', 'reg@example.com')->firstOrFail();

        // Member of the admin-designated default team...
        $this->assertTrue($user->fresh()->belongsToTeam($default), 'New user must join the flagged default team.');
        // ...and owns exactly one personal team (the redundant listener-created team is gone).
        $this->assertCount(1, $user->ownedTeams);
        $this->assertTrue($user->ownedTeams->first()->personal_team);
    }
}
