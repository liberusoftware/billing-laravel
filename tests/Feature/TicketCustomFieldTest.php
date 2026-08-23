<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Ticket;
use App\Models\TicketCustomField;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TicketCustomFieldTest extends TestCase
{
    use RefreshDatabase;

    public function test_custom_field_values_persist_on_ticket(): void
    {
        $ticket = Ticket::factory()->create(['custom_fields' => ['os' => 'Linux']]);

        $this->assertSame('Linux', $ticket->fresh()->custom_fields['os']);
    }

    public function test_required_custom_field_is_validated(): void
    {
        $user = User::factory()->create();
        $field = TicketCustomField::factory()->create(['is_required' => true]);

        $this->actingAs($user)
            ->post(route('tickets.store'), [
                'title' => 'Need help',
                'description' => 'Details',
                'priority' => 'low',
            ])
            ->assertSessionHasErrors("custom_fields.{$field->id}");
    }
}
