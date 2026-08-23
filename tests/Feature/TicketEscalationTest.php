<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Ticket;
use App\Models\TicketEscalationRule;
use App\Models\TicketResponse;
use App\Models\User;
use App\Services\TicketEscalationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TicketEscalationTest extends TestCase
{
    use RefreshDatabase;

    public function test_overdue_ticket_raises_priority(): void
    {
        $ticket = Ticket::factory()->create([
            'status' => 'open',
            'priority' => 'low',
            'created_at' => now()->subHours(3),
        ]);

        TicketEscalationRule::factory()->create([
            'minutes_without_response' => 60,
            'action' => 'raise_priority',
        ]);

        app(TicketEscalationService::class)->escalate();

        $this->assertSame('medium', $ticket->fresh()->priority);
    }

    public function test_escalation_reassigns_to_target(): void
    {
        $target = User::factory()->create();
        $ticket = Ticket::factory()->create([
            'status' => 'open',
            'assigned_to' => null,
            'created_at' => now()->subHours(3),
        ]);

        TicketEscalationRule::factory()->create([
            'minutes_without_response' => 60,
            'action' => 'reassign',
            'target_user_id' => $target->id,
        ]);

        app(TicketEscalationService::class)->escalate();

        $this->assertSame($target->id, $ticket->fresh()->assigned_to);
    }

    public function test_recent_response_keeps_ticket_below_threshold(): void
    {
        $ticket = Ticket::factory()->create([
            'status' => 'open',
            'priority' => 'low',
            'created_at' => now()->subHours(3),
        ]);
        TicketResponse::create([
            'ticket_id' => $ticket->id,
            'user_id' => $ticket->user_id,
            'message' => 'on it',
            'created_at' => now()->subMinutes(5),
            'updated_at' => now()->subMinutes(5),
        ]);

        TicketEscalationRule::factory()->create([
            'minutes_without_response' => 60,
            'action' => 'raise_priority',
        ]);

        app(TicketEscalationService::class)->escalate();

        $this->assertSame('low', $ticket->fresh()->priority);
    }
}
