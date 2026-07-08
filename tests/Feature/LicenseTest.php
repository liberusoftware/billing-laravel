<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\LicenseStatus;
use App\Models\License;
use App\Services\LicenseService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LicenseTest extends TestCase
{
    use RefreshDatabase;

    public function test_generating_license_creates_unique_prefixed_key(): void
    {
        $service = app(LicenseService::class);

        $a = $service->generate('ACME');
        $b = $service->generate('ACME');

        $this->assertStringStartsWith('ACME-', $a);
        $this->assertNotSame($a, $b);
    }

    public function test_license_persists_with_active_status_and_key(): void
    {
        $license = License::factory()->create();

        $this->assertSame(LicenseStatus::Active, $license->fresh()->status);
        $this->assertNotEmpty($license->license_key);
    }
}
