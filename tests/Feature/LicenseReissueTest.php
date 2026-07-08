<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\LicenseStatus;
use App\Models\License;
use App\Models\LicenseInstance;
use App\Services\LicenseService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use RuntimeException;
use Tests\TestCase;

class LicenseReissueTest extends TestCase
{
    use RefreshDatabase;

    public function test_reissue_clears_instances(): void
    {
        $license = License::factory()->create(['max_instances' => 2]);
        LicenseInstance::factory()->create(['license_id' => $license->id, 'identifier' => 'host-1']);
        LicenseInstance::factory()->create(['license_id' => $license->id, 'identifier' => 'host-2']);

        app(LicenseService::class)->reissue($license);

        $this->assertSame(0, $license->instances()->count());
        $this->assertSame(LicenseStatus::Active, $license->fresh()->status);
    }

    public function test_excessive_reissue_is_blocked(): void
    {
        config()->set('licensing.reissue_limit', 1);
        $license = License::factory()->create();
        $service = app(LicenseService::class);

        $service->reissue($license);

        $this->expectException(RuntimeException::class);
        $service->reissue($license);
    }
}
