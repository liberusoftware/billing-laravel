<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\LicenseService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class LicenseDownloadController extends Controller
{
    public function __construct(private readonly LicenseService $licenses) {}

    /**
     * Serve the protected product download only to a validly-licensed instance.
     */
    public function download(Request $request): StreamedResponse
    {
        $data = $request->validate([
            'license_key' => ['required', 'string'],
            'identifier' => ['required', 'string'],
        ]);

        $result = $this->licenses->validate($data['license_key'], [
            'identifier' => $data['identifier'],
            'ip_address' => $request->ip(),
        ]);

        abort_unless($result['valid'], 403, 'A valid license is required to download.');

        // ponytail: single protected artifact for now; key the path off the
        // license's product when per-product downloads are needed.
        return Storage::disk('local')->download('protected/release.zip', 'release.zip');
    }
}
