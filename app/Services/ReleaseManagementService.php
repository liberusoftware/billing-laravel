<?php

declare(strict_types=1);

namespace App\Services;

use App\Contracts\GitPlatformClient;
use App\Models\GitRelease;
use App\Models\GitRepository;
use Illuminate\Support\Facades\DB;

class ReleaseManagementService
{
    public function generateChangelog(GitRepository $repository, ?GitRelease $previous = null): string
    {
        $commits = $repository->syncRecords()
            ->where('record_type', 'commit')
            ->when($previous?->released_at, fn ($query, $releasedAt) => $query->where('external_created_at', '>', $releasedAt))
            ->orderBy('external_created_at')
            ->get();

        if ($commits->isEmpty()) {
            return 'No recorded changes.';
        }

        return $commits
            ->map(fn ($commit): string => '- '.trim((string) $commit->title)
                .($commit->author ? " ({$commit->author})" : ''))
            ->implode("\n");
    }

    public function create(
        GitRepository $repository,
        GitPlatformClient $client,
        string $version,
        string $name,
        ?string $changelog = null
    ): GitRelease {
        return DB::transaction(function () use ($repository, $client, $version, $name, $changelog): GitRelease {
            $previous = $repository->releases()->whereNotNull('released_at')->latest('released_at')->first();
            $notes = $changelog ?? $this->generateChangelog($repository, $previous);
            $external = $client->createRelease($repository, $version, $name, $notes);

            return $repository->releases()->create([
                'external_id' => $external['external_id'],
                'version' => $version,
                'name' => $name,
                'changelog' => $notes,
                'state' => $external['state'] ?? 'published',
                'web_url' => $external['web_url'] ?? null,
                'released_at' => now(),
            ]);
        });
    }

    public function trackDeployment(
        GitRelease $release,
        string $environment,
        string $status,
        bool $completed = false
    ): GitRelease {
        $release->update([
            'deployment_environment' => $environment,
            'deployment_status' => $status,
            'deployed_at' => $completed ? now() : null,
        ]);

        return $release->refresh();
    }
}
