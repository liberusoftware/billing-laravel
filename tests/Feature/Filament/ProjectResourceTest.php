<?php

declare(strict_types=1);

namespace Tests\Feature\Filament;

use App\Filament\Admin\Pages\ProjectReports;
use App\Filament\Admin\Resources\Projects\Pages\CreateProject;
use App\Filament\Admin\Resources\Projects\Pages\EditProject;
use App\Filament\Client\Resources\ProjectResource\Pages\ListProjects as ClientListProjects;
use App\Filament\Client\Resources\ProjectResource\Pages\ViewProject as ClientViewProject;
use App\Models\Customer;
use App\Models\Project;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ProjectResourceTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_create_project_for_customer(): void
    {
        $user = User::factory()->withPersonalTeam()->create();
        $this->actingAs($user);

        $customer = Customer::factory()->create(['team_id' => $user->currentTeam->id]);

        $panel = Filament::getPanel('admin');
        Filament::setCurrentPanel($panel);
        $panel->boot();
        Filament::setTenant($user->currentTeam);

        Livewire::test(CreateProject::class)
            ->fillForm([
                'customer_id' => $customer->id,
                'name' => 'Website redesign',
                'status' => 'open',
                'due_date' => '2026-08-01',
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('projects', [
            'name' => 'Website redesign',
            'customer_id' => $customer->id,
            'team_id' => $user->currentTeam->id,
            'status' => 'open',
            'created_by' => $user->id,
        ]);
    }

    public function test_admin_can_render_project_edit_page_with_relation_managers(): void
    {
        $user = User::factory()->withPersonalTeam()->create();
        $this->actingAs($user);

        $customer = Customer::factory()->create(['team_id' => $user->currentTeam->id]);
        $project = Project::factory()->create([
            'team_id' => $user->currentTeam->id,
            'customer_id' => $customer->id,
        ]);

        $panel = Filament::getPanel('admin');
        Filament::setCurrentPanel($panel);
        $panel->boot();
        Filament::setTenant($user->currentTeam);

        Livewire::test(EditProject::class, ['record' => $project->id])
            ->assertSuccessful();
    }

    public function test_client_sees_only_their_own_projects(): void
    {
        $user = User::factory()->withPersonalTeam()->create();
        $team = $user->currentTeam;

        $mine = Customer::factory()->create(['team_id' => $team->id, 'email' => $user->email]);
        $other = Customer::factory()->create(['team_id' => $team->id]);

        $myProject = Project::factory()->create(['team_id' => $team->id, 'customer_id' => $mine->id]);
        $otherProject = Project::factory()->create(['team_id' => $team->id, 'customer_id' => $other->id]);

        $this->actingAs($user);
        $panel = Filament::getPanel('client');
        Filament::setCurrentPanel($panel);
        $panel->boot();

        Livewire::test(ClientListProjects::class)
            ->assertCanSeeTableRecords([$myProject])
            ->assertCanNotSeeTableRecords([$otherProject]);
    }

    public function test_client_can_view_own_project_with_discussion(): void
    {
        $user = User::factory()->withPersonalTeam()->create();
        $team = $user->currentTeam;
        $customer = Customer::factory()->create(['team_id' => $team->id, 'email' => $user->email]);
        $project = Project::factory()->create(['team_id' => $team->id, 'customer_id' => $customer->id]);

        $this->actingAs($user);
        $panel = Filament::getPanel('client');
        Filament::setCurrentPanel($panel);
        $panel->boot();

        Livewire::test(ClientViewProject::class, ['record' => $project->id])
            ->assertSuccessful();
    }

    public function test_admin_can_render_project_reports_page(): void
    {
        $user = User::factory()->withPersonalTeam()->create();
        $this->actingAs($user);

        $customer = Customer::factory()->create(['team_id' => $user->currentTeam->id]);
        Project::factory()->create([
            'team_id' => $user->currentTeam->id,
            'customer_id' => $customer->id,
        ]);

        $panel = Filament::getPanel('admin');
        Filament::setCurrentPanel($panel);
        $panel->boot();
        Filament::setTenant($user->currentTeam);

        Livewire::test(ProjectReports::class)
            ->assertSuccessful();
    }
}
