<?php

declare(strict_types=1);

namespace Tests\Feature\Livewire;

use App\Livewire\Dashboard;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_component_can_render(): void
    {
        $user = User::factory()->withPersonalTeam()->create();

        Livewire::actingAs($user)
            ->test(Dashboard::class)
            ->assertOk();
    }

    public function test_dashboard_initializes_with_default_chart_preferences(): void
    {
        $user = User::factory()->withPersonalTeam()->create([
            'dashboard_preferences' => null,
        ]);

        $component = Livewire::actingAs($user)->test(Dashboard::class);

        $component->assertSet('chartPreferences', [
            'revenue' => true,
            'invoices' => true,
            'clients' => true,
        ]);
    }

    public function test_dashboard_toggle_chart_updates_preferences(): void
    {
        $user = User::factory()->withPersonalTeam()->create([
            'dashboard_preferences' => ['revenue' => true, 'invoices' => true, 'clients' => true],
        ]);

        Livewire::actingAs($user)
            ->test(Dashboard::class)
            ->call('toggleChart', 'revenue')
            ->assertSet('chartPreferences.revenue', false);
    }

    public function test_dashboard_active_charts_reflect_preferences(): void
    {
        $user = User::factory()->withPersonalTeam()->create([
            'dashboard_preferences' => ['revenue' => true, 'invoices' => false, 'clients' => true],
        ]);

        $component = Livewire::actingAs($user)->test(Dashboard::class);

        $activeCharts = $component->get('activeCharts');
        $this->assertContains('revenue', $activeCharts);
        $this->assertContains('clients', $activeCharts);
        $this->assertNotContains('invoices', $activeCharts);
    }
}
