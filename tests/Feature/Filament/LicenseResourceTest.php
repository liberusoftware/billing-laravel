<?php

declare(strict_types=1);

namespace Tests\Feature\Filament;

use App\Enums\LicenseStatus;
use App\Filament\Admin\Resources\Licenses\Pages\CreateLicense;
use App\Models\Customer;
use App\Models\License;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class LicenseResourceTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_issue_license(): void
    {
        $user = User::factory()->withPersonalTeam()->create();
        $this->actingAs($user);

        $customer = Customer::factory()->create();

        $panel = Filament::getPanel('admin');
        Filament::setCurrentPanel($panel);
        $panel->boot();
        Filament::setTenant($user->currentTeam);

        Livewire::test(CreateLicense::class)
            ->fillForm([
                'customer_id' => $customer->id,
                'status' => LicenseStatus::Active->value,
                'max_instances' => 1,
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $license = License::firstOrFail();

        $this->assertSame($customer->id, $license->customer_id);
        $this->assertSame($user->currentTeam->id, $license->team_id);
        $this->assertNotEmpty($license->license_key);
        $this->assertStringStartsWith('LIC-', $license->license_key);
    }
}
