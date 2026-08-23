<?php

namespace App\Policies;

use App\Models\Affiliate;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class AffiliatePolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->can('view_any_affiliate');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Affiliate $affiliate): bool
    {
        return $user->can('view_affiliate');
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->can('create_affiliate');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Affiliate $affiliate): bool
    {
        return $user->can('update_affiliate');
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Affiliate $affiliate): bool
    {
        return $user->can('delete_affiliate');
    }

    /**
     * Determine whether the user can bulk delete.
     */
    public function deleteAny(User $user): bool
    {
        return $user->can('delete_any_affiliate');
    }

    /**
     * Determine whether the user can permanently delete.
     */
    public function forceDelete(User $user, Affiliate $affiliate): bool
    {
        return $user->can('force_delete_affiliate');
    }

    /**
     * Determine whether the user can permanently bulk delete.
     */
    public function forceDeleteAny(User $user): bool
    {
        return $user->can('force_delete_any_affiliate');
    }

    /**
     * Determine whether the user can restore.
     */
    public function restore(User $user, Affiliate $affiliate): bool
    {
        return $user->can('restore_affiliate');
    }

    /**
     * Determine whether the user can bulk restore.
     */
    public function restoreAny(User $user): bool
    {
        return $user->can('restore_any_affiliate');
    }

    /**
     * Determine whether the user can replicate.
     */
    public function replicate(User $user, Affiliate $affiliate): bool
    {
        return $user->can('replicate_affiliate');
    }

    /**
     * Determine whether the user can reorder.
     */
    public function reorder(User $user): bool
    {
        return $user->can('reorder_affiliate');
    }
}
