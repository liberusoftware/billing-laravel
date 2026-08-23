<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Ticket;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TicketAssignmentTest extends TestCase
{
    use RefreshDatabase;

    public function test_ticket_can_be_assigned_to_staff(): void
    {
        $staff = User::factory()->create();
        $ticket = Ticket::factory()->create(['assigned_to' => $staff->id]);

        $this->assertTrue($ticket->assignee->is($staff));
    }

    public function test_assigned_scope_filters_by_staff(): void
    {
        $staff = User::factory()->create();
        $mine = Ticket::factory()->create(['assigned_to' => $staff->id]);
        $unassigned = Ticket::factory()->create();

        $ids = Ticket::assignedTo($staff->id)->pluck('id');

        $this->assertTrue($ids->contains($mine->id));
        $this->assertFalse($ids->contains($unassigned->id));
    }
}
