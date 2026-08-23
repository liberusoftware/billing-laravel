<?php

namespace App\Policies;

use App\Models\Customer;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class CustomerPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->can('view_any_customer');
    }

    public function view(User $user, Customer $_customer): bool
    {
        return $user->can('view_customer');
    }

    public function create(User $user): bool
    {
        return $user->can('create_customer');
    }

    public function update(User $user, Customer $_customer): bool
    {
        return $user->can('update_customer');
    }

    public function delete(User $user, Customer $_customer): bool
    {
        return $user->can('delete_customer');
    }

    public function deleteAny(User $user): bool
    {
        return $user->can('delete_any_customer');
    }

    public function forceDelete(User $user, Customer $_customer): bool
    {
        return $user->can('force_delete_customer');
    }

    public function forceDeleteAny(User $user): bool
    {
        return $user->can('force_delete_any_customer');
    }

    public function restore(User $user, Customer $_customer): bool
    {
        return $user->can('restore_customer');
    }

    public function restoreAny(User $user): bool
    {
        return $user->can('restore_any_customer');
    }

    public function replicate(User $user, Customer $_customer): bool
    {
        return $user->can('replicate_customer');
    }

    public function reorder(User $user): bool
    {
        return $user->can('reorder_customer');
    }
}
