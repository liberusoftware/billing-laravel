<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\LicenseStatus;
use App\Models\License;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class LicenseDownloadTest extends TestCase
{
    use RefreshDatabase;

    public function test_download_denied_without_valid_license(): void
    {
        $license = License::factory()->create(['status' => LicenseStatus::Suspended]);

        $this->postJson('/api/v1/license/download', [
            'license_key' => $license->license_key,
            'identifier' => 'host-1',
        ])->assertForbidden();
    }

    public function test_download_allowed_with_valid_license(): void
    {
        Storage::fake('local');
        Storage::disk('local')->put('protected/release.zip', 'payload');
        $license = License::factory()->create();

        $this->post('/api/v1/license/download', [
            'license_key' => $license->license_key,
            'identifier' => 'host-1',
        ])->assertOk();
    }
}
