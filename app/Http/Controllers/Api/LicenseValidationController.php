<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\LicenseService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LicenseValidationController extends Controller
{
    public function __construct(private readonly LicenseService $licenses) {}

    public function validateLicense(Request $request): JsonResponse
    {
        $data = $request->validate([
            'license_key' => ['required', 'string'],
            'identifier' => ['required', 'string'],
        ]);

        $result = $this->licenses->validate($data['license_key'], [
            'identifier' => $data['identifier'],
            // Never trust a client-supplied IP — record the connecting address.
            'ip_address' => $request->ip(),
        ]);

        return response()->json($result);
    }
}
