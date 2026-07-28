<?php

declare(strict_types=1);

namespace App\Services;

use App\Contracts\GitPlatformClient;
use App\Models\GitConnection;
use App\Models\GitRepository;
use App\Models\GitSyncRecord;
use Illuminate\Support\Facades\DB;

class GitSyncService
{
    /** @var list<string> */
    private const RECORD_TYPES = ['issue', 'milestone', 'change_request', 'commit', 'release'];

    public function syncConnection(GitConnection $connection, GitPlatformClient $client): int
    {
        $synced = 0;

        DB::transaction(function () use ($connection, $client, &$synced): void {
            foreach ($client->repositories() as $data) {
                $repository = GitRepository::query()->updateOrCreate(
                    ['git_connection_id' => $connection->id, 'external_id' => $data['external_id']],
                    [...$data, 'last_synced_at' => now()]
                );
                $synced += $this->syncRepository($repository, $client);
            }

            $connection->update(['last_synced_at' => now()]);
        });

        return $synced;
    }

    public function syncRepository(GitRepository $repository, GitPlatformClient $client): int
    {
        $synced = 0;

        foreach (self::RECORD_TYPES as $type) {
            foreach ($client->records($repository, $type) as $data) {
                if ($type === 'issue' && isset($data['payload']['pull_request'])) {
                    continue;
                }

                GitSyncRecord::query()->updateOrCreate(
                    [
                        'git_repository_id' => $repository->id,
                        'record_type' => $type,
                        'external_id' => $data['external_id'],
                    ],
                    $data
                );
                $synced++;
            }
        }

        $repository->update(['last_synced_at' => now()]);

        return $synced;
    }
}
