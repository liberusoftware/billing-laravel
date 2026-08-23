<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\LicenseStatus;
use App\Models\License;
use App\Services\LicenseService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LicenseValidationTest extends TestCase
{
    use RefreshDatabase;

    public function test_valid_key_returns_active(): void
    {
        $license = License::factory()->create(['max_instances' => 2]);

        $this->postJson('/api/v1/license/validate', [
            'license_key' => $license->license_key,
            'identifier' => 'host-1',
        ])->assertOk()->assertJson(['valid' => true, 'status' => 'active']);
    }

    public function test_suspended_key_returns_invalid(): void
    {
        $license = License::factory()->create(['status' => LicenseStatus::Suspended]);

        $this->postJson('/api/v1/license/validate', [
            'license_key' => $license->license_key,
            'identifier' => 'host-1',
        ])->assertJson(['valid' => false]);
    }

    public function test_exceeding_max_instances_is_rejected(): void
    {
        $license = License::factory()->create(['max_instances' => 1]);

        $this->postJson('/api/v1/license/validate', [
            'license_key' => $license->license_key,
            'identifier' => 'host-1',
        ])->assertJson(['valid' => true]);

        $this->postJson('/api/v1/license/validate', [
            'license_key' => $license->license_key,
            'identifier' => 'host-2',
        ])->assertJson(['valid' => false]);
    }

    public function test_activation_records_instance(): void
    {
        $license = License::factory()->create();

        app(LicenseService::class)->validate($license->license_key, ['identifier' => 'host-1']);

        $this->assertDatabaseHas('license_instances', [
            'license_id' => $license->id,
            'identifier' => 'host-1',
        ]);
    }

    public function test_local_key_verifies_offline(): void
    {
        $license = License::factory()->create();
        $service = app(LicenseService::class);

        $result = $service->validate($license->license_key, ['identifier' => 'host-1']);
        $localKey = $result['data']['local_key'];

        $this->assertTrue($service->verifyLocalKey($license->license_key, 'host-1', $localKey));
        $this->assertFalse($service->verifyLocalKey($license->license_key, 'host-1', 'tampered'));
    }

    public function test_non_valid_response_leaks_no_status(): void
    {
        $license = License::factory()->create(['status' => LicenseStatus::Suspended]);

        $this->postJson('/api/v1/license/validate', [
            'license_key' => $license->license_key,
            'identifier' => 'host-1',
        ])->assertOk()->assertExactJson(['valid' => false]);
    }

    public function test_instance_ip_is_server_ip_not_client_supplied(): void
    {
        $license = License::factory()->create();

        $this->postJson('/api/v1/license/validate', [
            'license_key' => $license->license_key,
            'identifier' => 'host-1',
            'ip_address' => '203.0.113.99', // forged by the caller
        ])->assertOk();

        $this->assertDatabaseMissing('license_instances', ['ip_address' => '203.0.113.99']);
        $this->assertDatabaseHas('license_instances', [
            'license_id' => $license->id,
            'identifier' => 'host-1',
            'ip_address' => '127.0.0.1',
        ]);
    }
}
