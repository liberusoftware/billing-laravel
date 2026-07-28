<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Enums\GitProvider;
use App\Models\GitConnection;
use App\Models\GitRepository;
use App\Services\Git\GitPlatformClientFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class GitPlatformClientsTest extends TestCase
{
    use RefreshDatabase;

    /** @return array<string, array{GitProvider, array<string, mixed>, array<string, mixed>}> */
    public static function providers(): array
    {
        return [
            'GitHub' => [
                GitProvider::GitHub,
                [
                    'id' => 101, 'full_name' => 'acme/billing', 'default_branch' => 'main',
                    'html_url' => 'https://github.com/acme/billing', 'private' => true,
                ],
                [
                    'sha' => 'abc123', 'commit' => ['message' => 'GitHub commit', 'author' => ['name' => 'Ada']],
                    'html_url' => 'https://github.com/acme/billing/commit/abc123',
                ],
            ],
            'GitLab' => [
                GitProvider::GitLab,
                [
                    'id' => 202, 'path_with_namespace' => 'acme/billing', 'default_branch' => 'main',
                    'web_url' => 'https://gitlab.com/acme/billing', 'visibility' => 'private',
                ],
                [
                    'id' => 'def456', 'title' => 'GitLab commit', 'author_name' => 'Lin',
                    'web_url' => 'https://gitlab.com/acme/billing/-/commit/def456',
                ],
            ],
            'Bitbucket' => [
                GitProvider::Bitbucket,
                [
                    'uuid' => '{repo}', 'full_name' => 'acme/billing', 'mainbranch' => ['name' => 'main'],
                    'links' => ['html' => ['href' => 'https://bitbucket.org/acme/billing']], 'is_private' => true,
                ],
                [
                    'hash' => 'fed789', 'message' => 'Bitbucket commit',
                    'author' => ['display_name' => 'Grace'],
                    'links' => ['html' => ['href' => 'https://bitbucket.org/acme/billing/commits/fed789']],
                ],
            ],
        ];
    }

    #[DataProvider('providers')]
    public function test_provider_normalizes_repository_and_commit(
        GitProvider $provider,
        array $repositoryPayload,
        array $commitPayload
    ): void {
        $connection = GitConnection::factory()->create([
            'provider' => $provider,
            'base_url' => "https://{$provider->value}.example.test/api",
            'access_token' => 'trusted-access-token',
        ]);
        Http::fake(function ($request) use ($provider, $repositoryPayload, $commitPayload) {
            if (str_contains($request->url(), 'commits')) {
                $payload = $provider === GitProvider::Bitbucket
                    ? ['values' => [$commitPayload]]
                    : [$commitPayload];

                return Http::response($payload);
            }

            $payload = $provider === GitProvider::Bitbucket
                ? ['values' => [$repositoryPayload]]
                : [$repositoryPayload];

            return Http::response($payload);
        });
        $client = app(GitPlatformClientFactory::class)->make($connection);

        $repositories = $client->repositories();
        $this->assertSame('acme/billing', $repositories[0]['full_name']);

        $repo = GitRepository::query()->create([
            'git_connection_id' => $connection->id,
            ...$repositories[0],
        ]);
        $commits = $client->records($repo, 'commit');

        $this->assertCount(1, $commits);
        $this->assertStringContainsString('commit', strtolower($commits[0]['title']));
        $this->assertNotSame('', $commits[0]['external_id']);
    }

    public function test_gitlab_uses_private_token_header(): void
    {
        $connection = GitConnection::factory()->create([
            'provider' => GitProvider::GitLab,
            'base_url' => 'https://gitlab.example.test/api/v4',
            'access_token' => 'trusted-access-token',
        ]);
        Http::fake(['*' => Http::response([])]);

        app(GitPlatformClientFactory::class)->make($connection)->repositories();

        Http::assertSent(fn ($request): bool => $request->hasHeader('PRIVATE-TOKEN', 'trusted-access-token'));
    }
}
