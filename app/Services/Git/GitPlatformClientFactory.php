<?php

declare(strict_types=1);

namespace App\Services\Git;

use App\Contracts\GitPlatformClient;
use App\Models\GitConnection;

class GitPlatformClientFactory
{
    public function make(GitConnection $connection): GitPlatformClient
    {
        return new HttpGitPlatformClient($connection);
    }
}
