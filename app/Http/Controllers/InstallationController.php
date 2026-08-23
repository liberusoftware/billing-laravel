<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Services\InstallationScriptService;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class InstallationController extends Controller
{
    public function install(Request $request): ?JsonResponse
    {
        $validated = $request->validate(
            [
                'control_panel' => 'required|string|in:cpanel,plesk,directadmin,virtualmin',
                'git_repo' => 'required|url',
                'domain' => 'required|string',
                'db_name' => 'required|string',
                'db_user' => 'required|string',
                'db_password' => 'required|string',
            ]
        );

        $installer = new InstallationScriptService(
            $validated['control_panel'],
            $validated['git_repo'],
            $validated['domain'],
            $validated['db_name'],
            $validated['db_user'],
            $validated['db_password']
        );

        try {
            $installer->execute();

            return response()->json(['message' => 'Installation completed successfully']);
        } catch (Exception $e) {
            return response()->json(
                ['error' => $e->getMessage()],
                500
            );
        }
    }
}
