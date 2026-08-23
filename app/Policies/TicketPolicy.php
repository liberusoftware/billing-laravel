<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Ticket;
use App\Models\User;

class TicketPolicy
{
    public function view(User $user, Ticket $ticket): bool
    {
        return $user->hasAnyRole(
            [
                'admin',
                'super_admin',
            ]
        ) || $ticket->user_id === $user->id;
    }

    public function update(User $user, Ticket $ticket): bool
    {
        return $user->hasAnyRole(
            [
                'admin',
                'super_admin',
            ]
        );
    }

    public function respond(User $user, Ticket $ticket): bool
    {
        return $user->hasAnyRole(
            [
                'admin',
                'super_admin',
            ]
        ) || $ticket->user_id === $user->id;
    }
}
