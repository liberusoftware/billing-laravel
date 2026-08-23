<?php

namespace App\Policies;

use App\Models\Quote;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class QuotePolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->can('view_any_quote');
    }

    public function view(User $user, Quote $_quote): bool
    {
        return $user->can('view_quote');
    }

    public function create(User $user): bool
    {
        return $user->can('create_quote');
    }

    public function update(User $user, Quote $_quote): bool
    {
        return $user->can('update_quote');
    }

    public function delete(User $user, Quote $_quote): bool
    {
        return $user->can('delete_quote');
    }

    public function deleteAny(User $user): bool
    {
        return $user->can('delete_any_quote');
    }

    public function forceDelete(User $user, Quote $_quote): bool
    {
        return $user->can('force_delete_quote');
    }

    public function forceDeleteAny(User $user): bool
    {
        return $user->can('force_delete_any_quote');
    }

    public function restore(User $user, Quote $_quote): bool
    {
        return $user->can('restore_quote');
    }

    public function restoreAny(User $user): bool
    {
        return $user->can('restore_any_quote');
    }

    public function replicate(User $user, Quote $_quote): bool
    {
        return $user->can('replicate_quote');
    }

    public function reorder(User $user): bool
    {
        return $user->can('reorder_quote');
    }
}
