<?php

namespace App\Policies;

use App\Models\Subscription;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class SubscriptionPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->can('view_any_subscription');
    }

    public function view(User $user, Subscription $_subscription): bool
    {
        return $user->can('view_subscription');
    }

    public function create(User $user): bool
    {
        return $user->can('create_subscription');
    }

    public function update(User $user, Subscription $_subscription): bool
    {
        return $user->can('update_subscription');
    }

    public function delete(User $user, Subscription $_subscription): bool
    {
        return $user->can('delete_subscription');
    }

    public function deleteAny(User $user): bool
    {
        return $user->can('delete_any_subscription');
    }

    public function forceDelete(User $user, Subscription $_subscription): bool
    {
        return $user->can('force_delete_subscription');
    }

    public function forceDeleteAny(User $user): bool
    {
        return $user->can('force_delete_any_subscription');
    }

    public function restore(User $user, Subscription $_subscription): bool
    {
        return $user->can('restore_subscription');
    }

    public function restoreAny(User $user): bool
    {
        return $user->can('restore_any_subscription');
    }

    public function replicate(User $user, Subscription $_subscription): bool
    {
        return $user->can('replicate_subscription');
    }

    public function reorder(User $user): bool
    {
        return $user->can('reorder_subscription');
    }
}
