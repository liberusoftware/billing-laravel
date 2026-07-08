<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\ProjectFile;
use App\Models\User;

class ProjectFilePolicy
{
    /**
     * A customer may download a file only when it is flagged customer_visible
     * and the file's project belongs to that customer (matched by email, the
     * same scoping the Client InvoiceResource uses since there is no client_id).
     */
    public function view(User $user, ProjectFile $file): bool
    {
        return $file->customer_visible
            && $file->project->customer->email === $user->email;
    }
}
