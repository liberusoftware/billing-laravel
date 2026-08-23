<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Actions\CreateProjectFromTicket;
use App\Models\Customer;
use App\Models\Project;
use App\Models\Team;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use RuntimeException;
use Tests\TestCase;

class TicketProjectLinkTest extends TestCase
{
    use RefreshDatabase;

    public function test_create_project_from_ticket_links_both(): void
    {
        $team = Team::factory()->create();
        $user = User::factory()->create();
        $customer = Customer::factory()->create([
            'user_id' => $user->id,
            'team_id' => $team->id,
        ]);
        $ticket = Ticket::create([
            'user_id' => $user->id,
            'title' => 'Broken faucet',
            'description' => 'Needs work',
            'priority' => 'high',
        ]);

        $project = (new CreateProjectFromTicket)($ticket);

        $this->assertSame($customer->id, $project->customer_id);
        $this->assertSame($project->id, $ticket->fresh()->project_id);
        $this->assertSame('Broken faucet', $project->name);
    }

    public function test_ticket_without_resolvable_customer_requires_manual_selection(): void
    {
        $user = User::factory()->create();
        $ticket = Ticket::create([
            'user_id' => $user->id,
            'title' => 'No customer',
            'description' => 'Needs work',
            'priority' => 'low',
        ]);

        // No customer resolvable and none passed: must fail.
        $this->expectException(RuntimeException::class);

        (new CreateProjectFromTicket)($ticket);
    }

    public function test_manual_customer_selection_links_when_unresolvable(): void
    {
        $team = Team::factory()->create();
        $user = User::factory()->create(['current_team_id' => $team->id]);
        $ticket = Ticket::create([
            'user_id' => $user->id,
            'title' => 'Manual link',
            'description' => 'Needs work',
            'priority' => 'low',
        ]);
        $customer = Customer::factory()->create(['team_id' => $team->id]);

        $project = (new CreateProjectFromTicket)($ticket, $customer);

        $this->assertSame($customer->id, $project->customer_id);
        $this->assertSame($project->id, $ticket->fresh()->project_id);
    }
}
