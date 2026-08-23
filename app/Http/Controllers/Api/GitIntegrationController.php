<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Enums\GitProvider;
use App\Http\Controllers\Controller;
use App\Jobs\SyncGitConnection;
use App\Models\GitConnection;
use App\Models\GitRelease;
use App\Models\GitRepository;
use App\Models\User;
use App\Services\Git\GitPlatformClientFactory;
use App\Services\ReleaseManagementService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\Response;

class GitIntegrationController extends Controller
{
    public function __construct(
        private readonly GitPlatformClientFactory $clients,
        private readonly ReleaseManagementService $releases,
    ) {}

    public function connections(Request $request): JsonResponse
    {
        return response()->json($this->connectionsQuery($request)->withCount('repositories')->paginate(25));
    }

    public function storeConnection(Request $request): JsonResponse
    {
        $teamId = $this->teamId($request);
        $data = $request->validate([
            'provider' => ['required', Rule::enum(GitProvider::class)],
            'name' => [
                'required', 'string', 'max:255',
                Rule::unique('git_connections', 'name')->where('team_id', $teamId)
                    ->where('provider', $request->input('provider')),
            ],
            'base_url' => 'required|url:https|max:2048',
            'access_token' => 'required|string|min:8|max:4096',
            'webhook_secret' => 'nullable|string|min:16|max:4096',
            'is_active' => 'sometimes|boolean',
        ]);
        $data['team_id'] = $teamId;

        return response()->json(GitConnection::query()->create($data), Response::HTTP_CREATED);
    }

    public function updateConnection(Request $request, int $connection): JsonResponse
    {
        $model = $this->connection($request, $connection);
        $model->update($request->validate([
            'name' => 'sometimes|string|max:255',
            'base_url' => 'sometimes|url:https|max:2048',
            'access_token' => 'sometimes|string|min:8|max:4096',
            'webhook_secret' => 'nullable|string|min:16|max:4096',
            'is_active' => 'sometimes|boolean',
        ]));

        return response()->json($model->refresh());
    }

    public function destroyConnection(Request $request, int $connection): Response
    {
        $this->connection($request, $connection)->delete();

        return response()->noContent();
    }

    public function sync(Request $request, int $connection): JsonResponse
    {
        $model = $this->connection($request, $connection);
        SyncGitConnection::dispatch($model->id);

        return response()->json(['queued' => true], Response::HTTP_ACCEPTED);
    }

    public function repositories(Request $request): JsonResponse
    {
        return response()->json(
            GitRepository::query()
                ->whereHas('connection', fn (Builder $query) => $query->where('team_id', $this->teamId($request)))
                ->withCount('syncRecords')
                ->paginate(25)
        );
    }

    public function repository(Request $request, int $repository): JsonResponse
    {
        return response()->json($this->repositoryForTeam($request, $repository)->load([
            'syncRecords', 'releases',
        ]));
    }

    public function createRelease(Request $request, int $repository): JsonResponse
    {
        $repo = $this->repositoryForTeam($request, $repository);
        $data = $request->validate([
            'version' => [
                'required', 'string', 'max:100',
                Rule::unique('git_releases', 'version')->where('git_repository_id', $repo->id),
            ],
            'name' => 'required|string|max:255',
            'changelog' => 'nullable|string|max:100000',
        ]);
        $release = $this->releases->create(
            $repo,
            $this->clients->make($repo->connection),
            $data['version'],
            $data['name'],
            $data['changelog'] ?? null
        );

        return response()->json($release, Response::HTTP_CREATED);
    }

    public function trackDeployment(Request $request, int $release): JsonResponse
    {
        $model = GitRelease::query()
            ->whereHas(
                'repository.connection',
                fn (Builder $query) => $query->where('team_id', $this->teamId($request))
            )
            ->findOrFail($release);
        $data = $request->validate([
            'environment' => 'required|string|max:100',
            'status' => 'required|string|max:100',
            'completed' => 'sometimes|boolean',
        ]);

        return response()->json($this->releases->trackDeployment(
            $model,
            $data['environment'],
            $data['status'],
            (bool) ($data['completed'] ?? false)
        ));
    }

    /** @return Builder<GitConnection> */
    private function connectionsQuery(Request $request): Builder
    {
        return GitConnection::query()->where('team_id', $this->teamId($request));
    }

    private function connection(Request $request, int $id): GitConnection
    {
        return $this->connectionsQuery($request)->findOrFail($id);
    }

    private function repositoryForTeam(Request $request, int $id): GitRepository
    {
        return GitRepository::query()
            ->whereHas('connection', fn (Builder $query) => $query->where('team_id', $this->teamId($request)))
            ->findOrFail($id);
    }

    private function teamId(Request $request): ?int
    {
        /** @var User|null $user */
        $user = $request->user();

        return $user?->current_team_id;
    }
}
