<?php

declare(strict_types=1);

namespace Tests\Feature\Filament;

use App\Enums\LicenseStatus;
use App\Filament\Client\Resources\LicenseResource\Pages\ListLicenses;
use App\Models\Customer;
use App\Models\License;
use App\Models\LicenseInstance;
use App\Models\User;
use Filament\Actions\Testing\TestAction;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ClientLicenseResourceTest extends TestCase
{
    use RefreshDatabase;

    private function actingInClientPanel(User $user): void
    {
        $this->actingAs($user);
        $panel = Filament::getPanel('client');
        Filament::setCurrentPanel($panel);
        $panel->boot();
    }

    public function test_client_sees_only_own_licenses(): void
    {
        $user = User::factory()->create(['email' => 'owner@example.com']);

        $mineCustomer = Customer::factory()->create(['email' => 'owner@example.com']);
        $mine = License::factory()->create(['customer_id' => $mineCustomer->id]);

        $theirsCustomer = Customer::factory()->create(['email' => 'other@example.com']);
        $theirs = License::factory()->create(['customer_id' => $theirsCustomer->id]);

        $this->actingInClientPanel($user);

        Livewire::test(ListLicenses::class)
            ->assertSuccessful()
            ->assertCanSeeTableRecords([$mine])
            ->assertCanNotSeeTableRecords([$theirs]);
    }

    public function test_client_can_reissue_own_license(): void
    {
        $user = User::factory()->create(['email' => 'owner@example.com']);
        $customer = Customer::factory()->create(['email' => 'owner@example.com']);
        $license = License::factory()->create([
            'customer_id' => $customer->id,
            'status' => LicenseStatus::Suspended,
        ]);
        LicenseInstance::create([
            'license_id' => $license->id,
            'identifier' => 'example.com',
            'last_validated_at' => now(),
        ]);

        $this->actingInClientPanel($user);

        Livewire::test(ListLicenses::class)
            ->callAction(TestAction::make('reissue')->table($license));

        $license->refresh();
        $this->assertSame(LicenseStatus::Active, $license->status);
        $this->assertSame(0, $license->instances()->count());
    }
}
