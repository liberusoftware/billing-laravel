<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Enums\LicenseStatus;
use App\Models\License;
use App\Models\LicenseInstance;
use App\Services\LicenseService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LicenseServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_local_key_is_returned_but_not_persisted(): void
    {
        $license = License::factory()->create();
        $service = app(LicenseService::class);

        $result = $service->validate($license->license_key, ['identifier' => 'host-1']);

        // Still handed to the caller so the SDK can cache it.
        $this->assertNotEmpty($result['data']['local_key']);
        $this->assertTrue($service->verifyLocalKey($license->license_key, 'host-1', $result['data']['local_key']));

        // ...but never written to the DB — it is a recomputable HMAC, not a stored secret.
        $this->assertDatabaseHas('license_instances', [
            'license_id' => $license->id,
            'identifier' => 'host-1',
            'local_key' => null,
        ]);
    }

    public function test_suspended_verdict_leaks_no_status(): void
    {
        $license = License::factory()->create(['status' => LicenseStatus::Suspended]);

        $result = app(LicenseService::class)->validate($license->license_key, ['identifier' => 'host-1']);

        $this->assertSame(['valid' => false], $result);
    }

    public function test_unknown_key_leaks_no_status(): void
    {
        $result = app(LicenseService::class)->validate('LIC-NO-SUCH-KEY', ['identifier' => 'host-1']);

        $this->assertSame(['valid' => false], $result);
    }

    public function test_instance_limit_leaks_no_status(): void
    {
        $license = License::factory()->create(['max_instances' => 1]);
        $service = app(LicenseService::class);

        $service->validate($license->license_key, ['identifier' => 'host-1']);
        $result = $service->validate($license->license_key, ['identifier' => 'host-2']);

        $this->assertSame(['valid' => false], $result);
    }

    public function test_models_hide_secret_keys_from_serialization(): void
    {
        $license = License::factory()->create();
        LicenseInstance::factory()->create([
            'license_id' => $license->id,
            'local_key' => 'legacy-value-should-not-leak',
        ]);

        $this->assertArrayNotHasKey('license_key', $license->fresh()->toArray());
        $this->assertArrayNotHasKey('local_key', $license->instances()->first()->toArray());
    }
}
