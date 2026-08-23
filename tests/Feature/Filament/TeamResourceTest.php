<?php

declare(strict_types=1);

namespace Tests\Feature\Filament;

use App\Filament\Admin\Resources\Teams\Pages\CreateTeam;
use App\Models\Team;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class TeamResourceTest extends TestCase
{
    use RefreshDatabase;

    private function bootAdminPanel(User $user): void
    {
        $this->actingAs($user);

        $panel = Filament::getPanel('admin');
        Filament::setCurrentPanel($panel);
        $panel->boot();
        Filament::setTenant($user->currentTeam);
    }

    private function superAdmin(): User
    {
        Role::findOrCreate('super_admin', 'web');
        $user = User::factory()->withPersonalTeam()->create();
        $user->assignRole('super_admin');

        return $user;
    }

    private function admin(): User
    {
        Role::findOrCreate('admin', 'web');
        $user = User::factory()->withPersonalTeam()->create();
        $user->assignRole('admin');

        return $user;
    }

    public function test_admin_can_create_team(): void
    {
        $user = $this->superAdmin();
        $this->bootAdminPanel($user);

        Livewire::test(CreateTeam::class)
            ->fillForm([
                'name' => 'Acme Corp',
                'user_id' => $user->id,
                'personal_team' => false,
                'is_default_for_registration' => false,
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('teams', [
            'name' => 'Acme Corp',
            'user_id' => $user->id,
        ]);
    }

    public function test_setting_default_for_registration_clears_previous_default(): void
    {
        $user = $this->superAdmin();
        $this->bootAdminPanel($user);

        $first = Team::factory()->create([
            'name' => 'First default',
            'user_id' => $user->id,
            'is_default_for_registration' => true,
        ]);

        Livewire::test(CreateTeam::class)
            ->fillForm([
                'name' => 'Second default',
                'user_id' => $user->id,
                'personal_team' => false,
                'is_default_for_registration' => true,
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $second = Team::where('name', 'Second default')->firstOrFail();

        $this->assertTrue($second->fresh()->is_default_for_registration);
        $this->assertFalse($first->fresh()->is_default_for_registration);
        $this->assertSame($second->id, Team::defaultForRegistration()?->id);
    }

    public function test_non_super_admin_cannot_set_owner_or_default_for_registration(): void
    {
        $user = $this->admin();
        $this->bootAdminPanel($user);

        $other = User::factory()->create();

        // The privileged fields are absent from the schema for a non-super_admin, so even a
        // tampered payload is dropped: owner defaults to the actor, default flag stays false.
        Livewire::test(CreateTeam::class)
            ->fillForm([
                'name' => 'Delegated Team',
            ])
            ->set('data.user_id', $other->id)
            ->set('data.is_default_for_registration', true)
            ->call('create')
            ->assertHasNoFormErrors();

        $team = Team::where('name', 'Delegated Team')->firstOrFail();

        $this->assertSame($user->id, $team->user_id);
        $this->assertFalse($team->is_default_for_registration);
    }
}
