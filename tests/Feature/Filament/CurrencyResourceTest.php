<?php

declare(strict_types=1);

namespace Tests\Feature\Filament;

use App\Filament\Admin\Resources\Currencies\Pages\CreateCurrency;
use App\Models\Currency;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class CurrencyResourceTest extends TestCase
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

    private function userWithRole(string $role): User
    {
        Role::findOrCreate($role, 'web');
        $user = User::factory()->withPersonalTeam()->create();
        $user->assignRole($role);

        return $user;
    }

    public function test_super_admin_can_set_base_and_exchange_rate(): void
    {
        $this->bootAdminPanel($this->userWithRole('super_admin'));

        Livewire::test(CreateCurrency::class)
            ->fillForm([
                'code' => 'EUR',
                'name' => 'Euro',
                'exchange_rate' => 0.85,
                'decimal_precision' => 2,
                'is_base' => true,
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $currency = Currency::where('code', 'EUR')->firstOrFail();
        $this->assertTrue($currency->is_base);
        $this->assertEquals(0.85, (float) $currency->exchange_rate);
    }

    public function test_non_super_admin_cannot_set_base_or_exchange_rate(): void
    {
        $this->bootAdminPanel($this->userWithRole('admin'));

        // is_base / exchange_rate are absent from the schema for a non-super_admin, so the
        // tampered values are dropped: rate falls back to the default (1), base stays false.
        Livewire::test(CreateCurrency::class)
            ->fillForm([
                'code' => 'GBP',
                'name' => 'Pound',
                'decimal_precision' => 2,
            ])
            ->set('data.exchange_rate', 99)
            ->set('data.is_base', true)
            ->call('create')
            ->assertHasNoFormErrors();

        $currency = Currency::where('code', 'GBP')->firstOrFail();
        $this->assertFalse($currency->is_base);
        $this->assertEquals(1.0, (float) $currency->exchange_rate);
    }
}
