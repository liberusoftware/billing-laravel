<?php

namespace App\Actions\Jetstream;

use Laravel\Jetstream\Contracts\DeletesUsers;

class DeleteUser implements DeletesUsers
{
    /**
     * Delete the given user.
     *
     * @param  mixed  $user
     */
    public function delete($user): void
    {
        $user->deleteProfilePhoto();
        $user->tokens()->delete();
        if (method_exists(
            $user,
            'connectedAccounts'
        )) {
            $user->connectedAccounts()->delete();
        }
        $user->delete();
    }
}
