<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Enums\GitProvider;
use App\Http\Controllers\Controller;
use App\Jobs\SyncGitConnection;
use App\Models\GitConnection;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class GitWebhookController extends Controller
{
    public function __invoke(Request $request, GitConnection $connection): JsonResponse
    {
        abort_unless($connection->is_active && $this->validSignature($request, $connection), Response::HTTP_UNAUTHORIZED);

        SyncGitConnection::dispatch($connection->id);

        return response()->json(['accepted' => true], Response::HTTP_ACCEPTED);
    }

    private function validSignature(Request $request, GitConnection $connection): bool
    {
        if ($connection->webhook_secret === null || $connection->webhook_secret === '') {
            return false;
        }

        if ($connection->provider === GitProvider::GitLab) {
            $token = $request->header('X-Gitlab-Token');

            return is_string($token) && hash_equals($connection->webhook_secret, $token);
        }

        $header = $connection->provider === GitProvider::GitHub
            ? $request->header('X-Hub-Signature-256')
            : $request->header('X-Hub-Signature');
        $expected = 'sha256='.hash_hmac('sha256', $request->getContent(), $connection->webhook_secret);

        return is_string($header) && hash_equals($expected, $header);
    }
}
