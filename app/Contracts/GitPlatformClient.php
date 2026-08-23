<?php

declare(strict_types=1);

namespace App\Contracts;

use App\Models\GitRepository;

interface GitPlatformClient
{
    /** @return list<array<string, mixed>> */
    public function repositories(): array;

    /** @return list<array<string, mixed>> */
    public function records(GitRepository $repository, string $type): array;

    /** @return array<string, mixed> */
    public function createRelease(GitRepository $repository, string $version, string $name, string $changelog): array;
}
