<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\File;
use App\Models\User;

class FilePolicy
{
    public function view(User $user, File $file): bool
    {
        return $user->id === $file->user_id
            || $file->shares()->where(
                'user_id',
                $user->id
            )->exists();
    }

    public function update(User $user, File $file): bool
    {
        return $user->id === $file->user_id
            || $file->shares()->where(
                'user_id',
                $user->id
            )
                ->whereIn(
                    'permission',
                    [
                        'write',
                        'admin',
                    ]
                )
                ->exists();
    }

    public function delete(User $user, File $file): bool
    {
        return $user->id === $file->user_id
            || $file->shares()->where(
                'user_id',
                $user->id
            )
                ->where(
                    'permission',
                    'admin'
                )
                ->exists();
    }
}
