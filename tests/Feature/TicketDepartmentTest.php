<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Filament\Admin\Resources\TicketDepartments\Pages\CreateTicketDepartment;
use App\Models\Ticket;
use App\Models\TicketDepartment;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class TicketDepartmentTest extends TestCase
{
    use RefreshDatabase;

    public function test_ticket_can_belong_to_department(): void
    {
        $department = TicketDepartment::factory()->create();
        $ticket = Ticket::factory()->create(['department_id' => $department->id]);

        $this->assertTrue($ticket->department->is($department));
        $this->assertTrue($department->tickets->contains($ticket));
    }

    public function test_active_scope_returns_only_enabled_departments(): void
    {
        $on = TicketDepartment::factory()->create(['is_active' => true]);
        $off = TicketDepartment::factory()->create(['is_active' => false]);

        $ids = TicketDepartment::active()->pluck('id');

        $this->assertTrue($ids->contains($on->id));
        $this->assertFalse($ids->contains($off->id));
    }

    public function test_admin_can_create_ticket_department(): void
    {
        $user = User::factory()->withPersonalTeam()->create();
        $this->actingAs($user);

        $panel = Filament::getPanel('admin');
        Filament::setCurrentPanel($panel);
        $panel->boot();
        Filament::setTenant($user->currentTeam);

        Livewire::test(CreateTicketDepartment::class)
            ->fillForm([
                'name' => 'Billing',
                'email' => 'billing@example.test',
                'is_active' => true,
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('ticket_departments', [
            'name' => 'Billing',
            'team_id' => $user->currentTeam->id,
        ]);
    }
}
