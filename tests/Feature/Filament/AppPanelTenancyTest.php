<?php

declare(strict_types=1);

namespace Tests\Feature\Filament;

use App\Filament\App\Resources\HostingAccounts\HostingAccountResource;
use App\Filament\App\Resources\HostingAccounts\Pages\ListHostingAccounts;
use App\Models\HostingAccount;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class AppPanelTenancyTest extends TestCase
{
    use RefreshDatabase;

    private function actingInAppPanel(User $user): User
    {
        $this->actingAs($user);
        $panel = Filament::getPanel('app');
        Filament::setCurrentPanel($panel);
        $panel->boot();
        Filament::setTenant($user->currentTeam);

        return $user;
    }

    public function test_app_panel_hosting_accounts_are_scoped_to_current_team(): void
    {
        Permission::findOrCreate('view_any_hosting::account', 'web');

        $user = User::factory()->withPersonalTeam()->create();
        $user->givePermissionTo('view_any_hosting::account');
        $ownTeam = $user->currentTeam;
        $otherTeam = User::factory()->withPersonalTeam()->create()->currentTeam;

        $mine = HostingAccount::factory()->create(['team_id' => $ownTeam->id]);
        $theirs = HostingAccount::factory()->create(['team_id' => $otherTeam->id]);

        $this->actingInAppPanel($user);

        Livewire::test(ListHostingAccounts::class)
            ->assertSuccessful()
            ->assertCanSeeTableRecords([$mine])
            ->assertCanNotSeeTableRecords([$theirs]);
    }

    public function test_app_panel_resource_denied_without_permission(): void
    {
        $this->actingInAppPanel(User::factory()->withPersonalTeam()->create());

        $this->assertFalse(HostingAccountResource::canViewAny());
    }

    public function test_user_cannot_access_another_teams_tenant(): void
    {
        $user = User::factory()->withPersonalTeam()->create();
        $otherTeam = User::factory()->withPersonalTeam()->create()->currentTeam;

        $this->assertTrue($user->canAccessTenant($user->currentTeam));
        $this->assertFalse($user->canAccessTenant($otherTeam));
    }
}
