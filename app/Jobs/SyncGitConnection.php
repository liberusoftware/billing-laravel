<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\GitConnection;
use App\Services\Git\GitPlatformClientFactory;
use App\Services\GitSyncService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class SyncGitConnection implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public function __construct(public readonly int $connectionId) {}

    public function handle(GitSyncService $sync, GitPlatformClientFactory $clients): void
    {
        $connection = GitConnection::query()->where('is_active', true)->findOrFail($this->connectionId);
        $sync->syncConnection($connection, $clients->make($connection));
    }
}
