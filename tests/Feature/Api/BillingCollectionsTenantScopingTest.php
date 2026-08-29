<?php

declare(strict_types=1);

namespace Tests\Feature\Api;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Liberu\Billing\Collections\Models\CollectionCase;
use Tests\TestCase;

class BillingCollectionsTenantScopingTest extends TestCase
{
    use RefreshDatabase;

    public function test_case_mutations_cannot_access_another_team_case(): void
    {
        $user = User::factory()->withPersonalTeam()->create();
        $otherTeam = User::factory()->withPersonalTeam()->create()->currentTeam;
        $case = CollectionCase::query()->create([
            'team_id' => $otherTeam->id,
            'type' => 'retry',
            'status' => 'open',
            'amount_minor' => 100,
            'currency' => 'USD',
        ]);
        Sanctum::actingAs($user, ['*']);

        $this->postJson('/api/v1/billing/collections/'.$case->getKey().'/recover')
            ->assertNotFound();

        $this->assertDatabaseHas('billing_collection_cases', [
            'id' => $case->getKey(), 'team_id' => $otherTeam->id, 'status' => 'open',
        ]);
    }
}
