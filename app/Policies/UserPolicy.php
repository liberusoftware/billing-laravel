<?php

namespace App\Policies;

use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;

class UserPolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('view_any:user');
    }

    public function view(AuthUser $authUser): bool
    {
        return $authUser->can('view:user');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('create:user');
    }

    public function update(AuthUser $authUser): bool
    {
        return $authUser->can('update:user');
    }

    public function delete(AuthUser $authUser): bool
    {
        return $authUser->can('delete:user');
    }

    public function restore(AuthUser $authUser): bool
    {
        return $authUser->can('restore:user');
    }

    public function forceDelete(AuthUser $authUser): bool
    {
        return $authUser->can('force_delete:user');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('force_delete_any:user');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('restore_any:user');
    }

    public function replicate(AuthUser $authUser): bool
    {
        return $authUser->can('replicate:user');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('reorder:user');
    }
}
