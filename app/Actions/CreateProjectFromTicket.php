<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\Customer;
use App\Models\Project;
use App\Models\Ticket;
use RuntimeException;

class CreateProjectFromTicket
{
    /**
     * Spin up a project from a support ticket, linking both records.
     *
     * Tickets are keyed by user_id, so the billing customer is resolved via
     * the ticket's user. When that mapping is absent the caller must supply a
     * customer explicitly (e.g. via a Select in the admin UI).
     */
    public function __invoke(Ticket $ticket, ?Customer $customer = null): Project
    {
        $customer ??= $ticket->user?->customer;

        if (! $customer instanceof Customer) {
            throw new RuntimeException(
                'Ticket has no resolvable customer; select one manually.'
            );
        }

        $project = Project::create([
            'team_id' => $customer->team_id ?? $ticket->user?->current_team_id,
            'customer_id' => $customer->id,
            'created_by' => $ticket->user_id,
            'name' => $ticket->title,
            'description' => $ticket->description,
        ]);

        $ticket->update(['project_id' => $project->id]);

        return $project;
    }
}
