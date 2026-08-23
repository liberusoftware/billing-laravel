<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Project;
use App\Models\ProjectMessage;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProjectMessageTest extends TestCase
{
    use RefreshDatabase;

    public function test_customer_can_post_message_to_their_project(): void
    {
        $user = User::factory()->create();
        $customer = Customer::factory()->create(['email' => $user->email]);
        $project = Project::factory()->create(['customer_id' => $customer->id]);

        $message = ProjectMessage::postAsCustomer($project, $user, 'Hello team');

        $this->assertSame('customer', $message->author_type);
        $this->assertSame($user->id, $message->author_id);
        $this->assertDatabaseHas('project_messages', [
            'project_id' => $project->id,
            'author_type' => 'customer',
            'body' => 'Hello team',
        ]);
    }

    public function test_customer_cannot_post_to_others_project(): void
    {
        $user = User::factory()->create();
        // Factory gives the project a customer with a random email != $user->email.
        $project = Project::factory()->create();

        $this->expectException(AuthorizationException::class);

        ProjectMessage::postAsCustomer($project, $user, 'Sneaky');
    }
}
