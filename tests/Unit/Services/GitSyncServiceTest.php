<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Contracts\GitPlatformClient;
use App\Models\GitConnection;
use App\Models\GitRepository;
use App\Services\GitSyncService;
use App\Services\ReleaseManagementService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GitSyncServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_sync_imports_repository_and_each_supported_record_type_idempotently(): void
    {
        $connection = GitConnection::factory()->create();
        $client = new FakeGitPlatformClient;
        $sync = app(GitSyncService::class);

        $this->assertSame(5, $sync->syncConnection($connection, $client));
        $this->assertSame(5, $sync->syncConnection($connection, $client));

        $this->assertDatabaseCount('git_repositories', 1);
        $this->assertDatabaseCount('git_sync_records', 5);
        foreach (['issue', 'milestone', 'change_request', 'commit', 'release'] as $type) {
            $this->assertDatabaseHas('git_sync_records', ['record_type' => $type]);
        }
        $this->assertNotNull($connection->refresh()->last_synced_at);
    }

    public function test_release_creation_generates_changelog_and_tracks_deployment(): void
    {
        $connection = GitConnection::factory()->create();
        $repository = GitRepository::query()->create([
            'git_connection_id' => $connection->id,
            'external_id' => 'repo-1',
            'full_name' => 'acme/billing',
            'web_url' => 'https://git.example/acme/billing',
        ]);
        $repository->syncRecords()->create([
            'record_type' => 'commit',
            'external_id' => 'abc123',
            'title' => 'Add billing engine',
            'author' => 'Ada',
            'external_created_at' => now(),
        ]);
        $service = app(ReleaseManagementService::class);

        $release = $service->create($repository, new FakeGitPlatformClient, 'v1.2.0', 'Billing release');

        $this->assertSame('v1.2.0', $release->version);
        $this->assertStringContainsString('Add billing engine (Ada)', $release->changelog);
        $this->assertSame('external-release', $release->external_id);

        $deployed = $service->trackDeployment($release, 'production', 'succeeded', true);
        $this->assertSame('production', $deployed->deployment_environment);
        $this->assertSame('succeeded', $deployed->deployment_status);
        $this->assertNotNull($deployed->deployed_at);
    }
}

final class FakeGitPlatformClient implements GitPlatformClient
{
    public function repositories(): array
    {
        return [[
            'external_id' => 'repo-1',
            'full_name' => 'acme/billing',
            'default_branch' => 'main',
            'web_url' => 'https://git.example/acme/billing',
            'is_private' => true,
        ]];
    }

    public function records(GitRepository $repository, string $type): array
    {
        return [[
            'external_id' => "{$type}-1",
            'title' => ucfirst($type),
            'state' => 'open',
            'web_url' => "https://git.example/{$type}/1",
            'payload' => ['kind' => $type],
        ]];
    }

    public function createRelease(
        GitRepository $repository,
        string $version,
        string $name,
        string $changelog
    ): array {
        return [
            'external_id' => 'external-release',
            'title' => $name,
            'state' => 'published',
            'web_url' => "https://git.example/releases/{$version}",
        ];
    }
}
