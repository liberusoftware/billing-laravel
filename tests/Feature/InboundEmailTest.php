<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Ticket;
use App\Models\TicketDepartment;
use App\Models\TicketResponse;
use App\Models\User;
use App\Services\InboundEmailService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InboundEmailTest extends TestCase
{
    use RefreshDatabase;

    public function test_inbound_email_appends_response_to_existing_ticket(): void
    {
        $sender = User::factory()->create(['email' => 'jane@example.com']);
        $ticket = Ticket::factory()->create(['user_id' => $sender->id]);

        $result = app(InboundEmailService::class)->handle([
            'from' => 'jane@example.com',
            'to' => 'support@example.com',
            'subject' => "Re: [Ticket #{$ticket->id}] My issue",
            'body' => 'Here is more detail.',
        ]);

        $this->assertInstanceOf(TicketResponse::class, $result);
        $this->assertDatabaseHas('ticket_responses', [
            'ticket_id' => $ticket->id,
            'user_id' => $sender->id,
            'message' => 'Here is more detail.',
        ]);
    }

    public function test_inbound_email_opens_new_ticket_for_department(): void
    {
        $sender = User::factory()->create(['email' => 'bob@example.com']);
        $department = TicketDepartment::factory()->create([
            'email' => 'billing@example.com',
            'is_active' => true,
        ]);

        $result = app(InboundEmailService::class)->handle([
            'from' => 'bob@example.com',
            'to' => 'billing@example.com',
            'subject' => 'Cannot pay invoice',
            'body' => 'My card is declined.',
        ]);

        $this->assertInstanceOf(Ticket::class, $result);
        $this->assertDatabaseHas('tickets', [
            'id' => $result->id,
            'user_id' => $sender->id,
            'department_id' => $department->id,
            'title' => 'Cannot pay invoice',
            'status' => 'open',
        ]);
    }
}
