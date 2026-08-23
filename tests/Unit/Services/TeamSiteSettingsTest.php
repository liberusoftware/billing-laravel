<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Models\SiteSettings;
use App\Models\User;
use App\Services\SiteSettingsService;
use App\Services\TeamManagementService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class TeamSiteSettingsTest extends TestCase
{
    use RefreshDatabase;

    private TeamManagementService $teams;

    private SiteSettingsService $settings;

    protected function setUp(): void
    {
        parent::setUp();
        $this->teams = app(TeamManagementService::class);
        $this->settings = app(SiteSettingsService::class);
    }

    // --- TeamManagementService ---

    public function test_create_personal_team_creates_owned_team_and_switches(): void
    {
        $user = User::factory()->create(['name' => 'John Smith']);

        $this->teams->createPersonalTeamForUser($user);

        $team = $user->ownedTeams()->first();
        $this->assertNotNull($team);
        $this->assertSame("John's Team", $team->name);
        $this->assertTrue((bool) $team->personal_team);
        $this->assertSame($team->id, $user->fresh()->current_team_id);
    }

    public function test_assign_user_to_default_team_creates_and_switches_to_owned_team(): void
    {
        $user = User::factory()->create(['name' => 'Jane Doe']);

        $this->teams->assignUserToDefaultTeam($user);

        $user->refresh();
        $team = $user->currentTeam;
        $this->assertNotNull($team);
        $this->assertSame("Jane Doe's Team", $team->name);
        $this->assertTrue($user->ownsTeam($team));
        $this->assertTrue($user->belongsToTeam($team));
    }

    public function test_assign_default_team_does_not_share_team_between_users_with_same_name(): void
    {
        $a = User::factory()->create(['name' => 'Same Name']);
        $b = User::factory()->create(['name' => 'Same Name']);

        $this->teams->assignUserToDefaultTeam($a);
        $this->teams->assignUserToDefaultTeam($b);

        $this->assertNotSame(
            $a->fresh()->current_team_id,
            $b->fresh()->current_team_id,
            'Users with identical names must not share a default team.'
        );
    }

    // --- SiteSettingsService ---

    public function test_get_returns_value_for_key(): void
    {
        SiteSettings::create(['name' => 'Acme', 'currency' => 'USD']);

        $this->assertSame('Acme', $this->settings->get('name'));
        $this->assertSame('USD', $this->settings->get('currency'));
    }

    public function test_get_without_key_returns_model_instance(): void
    {
        SiteSettings::create(['name' => 'Acme']);

        $this->assertInstanceOf(SiteSettings::class, $this->settings->get());
    }

    public function test_get_returns_empty_model_when_no_row_exists(): void
    {
        $this->assertInstanceOf(SiteSettings::class, $this->settings->get());
        $this->assertNull($this->settings->get('name'));
    }

    public function test_clear_busts_the_cache(): void
    {
        SiteSettings::create(['name' => 'Before']);

        $this->assertSame('Before', $this->settings->get('name'));

        // Mutate the row directly so model events / cache are not refreshed.
        DB::table('site_settings')->update(['name' => 'After']);

        $this->assertSame('Before', $this->settings->get('name'), 'Value should still be cached.');

        $this->settings->clear();

        $this->assertSame('After', $this->settings->get('name'), 'clear() must bust the cache.');
    }
}
