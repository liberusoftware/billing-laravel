<?php

declare(strict_types=1);

namespace App\Services\Git;

use App\Contracts\GitPlatformClient;
use App\Enums\GitProvider;
use App\Models\GitConnection;
use App\Models\GitRepository;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use InvalidArgumentException;
use RuntimeException;
use Throwable;

class HttpGitPlatformClient implements GitPlatformClient
{
    public function __construct(private readonly GitConnection $connection) {}

    public function repositories(): array
    {
        $path = match ($this->connection->provider) {
            GitProvider::GitHub => '/user/repos?per_page=100',
            GitProvider::GitLab => '/projects?membership=true&per_page=100',
            GitProvider::Bitbucket => '/repositories?role=member&pagelen=100',
        };

        return array_map(fn (array $item): array => $this->normalizeRepository($item), $this->getAll($path));
    }

    public function records(GitRepository $repository, string $type): array
    {
        $path = $this->recordPath($repository, $type);

        if ($path === null) {
            return [];
        }

        return array_map(
            fn (array $item): array => $this->normalizeRecord($item, $type),
            $this->getAll($path)
        );
    }

    public function createRelease(
        GitRepository $repository,
        string $version,
        string $name,
        string $changelog
    ): array {
        [$path, $payload] = match ($this->connection->provider) {
            GitProvider::GitHub => [
                "/repos/{$repository->full_name}/releases",
                ['tag_name' => $version, 'name' => $name, 'body' => $changelog],
            ],
            GitProvider::GitLab => [
                '/projects/'.rawurlencode($repository->external_id).'/releases',
                ['tag_name' => $version, 'name' => $name, 'description' => $changelog],
            ],
            GitProvider::Bitbucket => [
                "/repositories/{$repository->full_name}/refs/tags",
                ['name' => $version, 'target' => ['hash' => $repository->default_branch]],
            ],
        };

        return $this->normalizeRecord($this->send('post', $path, $payload)->json(), 'release');
    }

    private function recordPath(GitRepository $repository, string $type): ?string
    {
        $encodedId = rawurlencode($repository->external_id);

        return match ($this->connection->provider) {
            GitProvider::GitHub => match ($type) {
                'issue' => "/repos/{$repository->full_name}/issues?state=all&per_page=100",
                'milestone' => "/repos/{$repository->full_name}/milestones?state=all&per_page=100",
                'change_request' => "/repos/{$repository->full_name}/pulls?state=all&per_page=100",
                'commit' => "/repos/{$repository->full_name}/commits?per_page=100",
                'release' => "/repos/{$repository->full_name}/releases?per_page=100",
                default => throw new InvalidArgumentException("Unsupported Git record type: {$type}"),
            },
            GitProvider::GitLab => match ($type) {
                'issue' => "/projects/{$encodedId}/issues?scope=all&per_page=100",
                'milestone' => "/projects/{$encodedId}/milestones?per_page=100",
                'change_request' => "/projects/{$encodedId}/merge_requests?scope=all&per_page=100",
                'commit' => "/projects/{$encodedId}/repository/commits?per_page=100",
                'release' => "/projects/{$encodedId}/releases?per_page=100",
                default => throw new InvalidArgumentException("Unsupported Git record type: {$type}"),
            },
            GitProvider::Bitbucket => match ($type) {
                'issue' => "/repositories/{$repository->full_name}/issues?pagelen=100",
                'milestone' => null,
                'change_request' => "/repositories/{$repository->full_name}/pullrequests?state=OPEN&state=MERGED&state=DECLINED&pagelen=100",
                'commit' => "/repositories/{$repository->full_name}/commits?pagelen=100",
                'release' => "/repositories/{$repository->full_name}/refs/tags?pagelen=100",
                default => throw new InvalidArgumentException("Unsupported Git record type: {$type}"),
            },
        };
    }

    /** @return list<array<string, mixed>> */
    private function getAll(string $path): array
    {
        $items = [];
        $next = $path;
        $pages = 0;

        while ($next !== '' && $pages++ < 100) {
            $response = $this->send('get', $next);
            $body = $response->json();
            $pageItems = $this->connection->provider === GitProvider::Bitbucket
                ? ($body['values'] ?? [])
                : $body;

            if (! is_array($pageItems)) {
                throw new RuntimeException('Git provider returned an invalid collection.');
            }

            foreach ($pageItems as $item) {
                if (is_array($item)) {
                    $items[] = $item;
                }
            }

            $next = $this->nextPage($response, $body);
        }

        return $items;
    }

    private function nextPage(Response $response, mixed $body): string
    {
        if ($this->connection->provider === GitProvider::Bitbucket) {
            return is_array($body) && is_string($body['next'] ?? null) ? $body['next'] : '';
        }

        if ($this->connection->provider === GitProvider::GitLab) {
            $nextPage = $response->header('X-Next-Page');

            return $nextPage !== ''
                ? $this->withQueryParameter((string) $response->effectiveUri(), 'page', $nextPage)
                : '';
        }

        $link = $response->header('Link');
        if ($link !== '' && preg_match('/<([^>]+)>; rel="next"/', $link, $matches) === 1) {
            return $matches[1];
        }

        return '';
    }

    private function withQueryParameter(string $url, string $key, string $value): string
    {
        $separator = str_contains($url, '?') ? '&' : '?';

        return $url.$separator.rawurlencode($key).'='.rawurlencode($value);
    }

    /** @param array<string, mixed> $item @return array<string, mixed> */
    private function normalizeRepository(array $item): array
    {
        return match ($this->connection->provider) {
            GitProvider::GitHub => [
                'external_id' => (string) $item['id'],
                'full_name' => $item['full_name'],
                'default_branch' => $item['default_branch'] ?? 'main',
                'web_url' => $item['html_url'],
                'is_private' => $item['private'] ?? true,
                'external_updated_at' => $item['updated_at'] ?? null,
            ],
            GitProvider::GitLab => [
                'external_id' => (string) $item['id'],
                'full_name' => $item['path_with_namespace'],
                'default_branch' => $item['default_branch'] ?? 'main',
                'web_url' => $item['web_url'],
                'is_private' => ($item['visibility'] ?? 'private') !== 'public',
                'external_updated_at' => $item['last_activity_at'] ?? null,
            ],
            GitProvider::Bitbucket => [
                'external_id' => (string) ($item['uuid'] ?? $item['full_name']),
                'full_name' => $item['full_name'],
                'default_branch' => $item['mainbranch']['name'] ?? 'main',
                'web_url' => $item['links']['html']['href'],
                'is_private' => $item['is_private'] ?? true,
                'external_updated_at' => $item['updated_on'] ?? null,
            ],
        };
    }

    /** @param array<string, mixed> $item @return array<string, mixed> */
    private function normalizeRecord(array $item, string $type): array
    {
        $id = $item['id'] ?? $item['sha'] ?? $item['hash'] ?? $item['uuid'] ?? $item['name'] ?? $item['tag_name'] ?? null;
        $title = $item['title'] ?? $item['name'] ?? $item['message'] ?? $item['commit']['message'] ?? $item['tag_name'] ?? null;
        $author = $item['user']['login'] ?? $item['author']['username'] ?? $item['author_name']
            ?? $item['author']['display_name'] ?? $item['commit']['author']['name'] ?? null;

        return [
            'external_id' => (string) $id,
            'title' => is_string($title) ? $title : null,
            'state' => $item['state'] ?? $item['status'] ?? ($type === 'commit' ? 'committed' : 'published'),
            'web_url' => $item['html_url'] ?? $item['web_url'] ?? $item['_links']['self'] ?? $item['links']['html']['href'] ?? null,
            'author' => is_string($author) ? $author : null,
            'external_created_at' => $item['created_at'] ?? $item['created_on'] ?? $item['authored_date'] ?? null,
            'external_updated_at' => $item['updated_at'] ?? $item['updated_on'] ?? $item['committed_date'] ?? null,
            'payload' => $item,
        ];
    }

    /** @param array<string, mixed> $payload */
    private function send(string $method, string $path, array $payload = []): Response
    {
        try {
            return $this->request()->send($method, $path, $payload === [] ? [] : ['json' => $payload])->throw();
        } catch (Throwable $exception) {
            report($exception);

            throw new RuntimeException("{$this->connection->provider->value} operation failed.");
        }
    }

    private function request(): PendingRequest
    {
        $request = Http::baseUrl(rtrim($this->connection->base_url, '/'))
            ->acceptJson()
            ->timeout(20)
            ->retry(2, 200);

        return match ($this->connection->provider) {
            GitProvider::GitLab => $request->withHeaders(['PRIVATE-TOKEN' => $this->connection->access_token]),
            default => $request->withToken($this->connection->access_token),
        };
    }
}
