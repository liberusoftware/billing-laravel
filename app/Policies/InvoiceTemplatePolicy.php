<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\InvoiceTemplate;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class InvoiceTemplatePolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, InvoiceTemplate $template): bool
    {
        return $user->current_team_id === $template->team_id;
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, InvoiceTemplate $template): bool
    {
        return $user->current_team_id === $template->team_id;
    }

    public function delete(User $user, InvoiceTemplate $template): bool
    {
        return $user->current_team_id === $template->team_id;
    }
}
