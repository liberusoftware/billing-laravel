<?php

declare(strict_types=1);

namespace Tests\Feature\Api;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Liberu\Billing\Communications\Models\CommunicationService;
use Tests\TestCase;

class BillingCommunicationsTenantScopingTest extends TestCase
{
    use RefreshDatabase;

    public function test_number_provisioning_cannot_attach_another_team_service(): void
    {
        $user = User::factory()->withPersonalTeam()->create();
        $user->update(['current_team_id' => $user->currentTeam->id]);
        $otherTeam = User::factory()->withPersonalTeam()->create()->currentTeam;
        $service = CommunicationService::query()->create([
            'team_id' => $otherTeam->id,
            'name' => 'Other team service',
            'status' => 'active',
        ]);
        Sanctum::actingAs($user, ['*']);

        $this->postJson('/api/v1/billing/communications/numbers', [
            'number' => '+15555550100',
            'service_id' => $service->getKey(),
        ])->assertNotFound();

        $this->assertDatabaseMissing('billing_communication_numbers', [
            'number' => '+15555550100',
        ]);
    }
}
